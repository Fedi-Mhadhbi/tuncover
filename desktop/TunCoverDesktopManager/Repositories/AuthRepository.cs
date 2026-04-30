using MySql.Data.MySqlClient;
using System.Net.Http;
using System.Text;
using System.Text.Json;
using TunCoverDesktopManager.Data;
using TunCoverDesktopManager.Models;

namespace TunCoverDesktopManager.Repositories;

public sealed class AuthRepository
{
    private readonly DbManager _dbManager = new();

    public User? Authenticate(string email, string? password = null)
    {
        using var connection = _dbManager.CreateConnection();
        connection.Open();

        const string sql = @"
            SELECT id_utilisateur, nom, prenom, email, role, statut, COALESCE(telephone, '') AS telephone, COALESCE(mot_de_passe, '') AS mot_de_passe
            FROM utilisateurs
            WHERE email = @email
            LIMIT 1;";

        using var command = new MySqlCommand(sql, connection);
        command.Parameters.AddWithValue("@email", email.Trim());

        using var reader = command.ExecuteReader();
        if (!reader.Read())
        {
            return null;
        }

        var storedPassword = reader.GetString("mot_de_passe");
        if (!VerifyPassword(email.Trim(), password, storedPassword))
        {
            return null;
        }

        return new User
        {
            Id = reader.GetInt32("id_utilisateur"),
            Nom = reader.GetString("nom"),
            Prenom = reader.GetString("prenom"),
            Email = reader.GetString("email"),
            Role = reader.GetString("role"),
            Statut = reader.GetString("statut"),
            Telephone = reader.GetString("telephone")
        };
    }

    private static bool VerifyPassword(string email, string? inputPassword, string storedPassword)
    {
        if (string.IsNullOrWhiteSpace(inputPassword))
        {
            return false;
        }

        if (string.IsNullOrWhiteSpace(storedPassword))
        {
            return false;
        }

        if (!storedPassword.StartsWith("$2", StringComparison.Ordinal))
        {
            return string.Equals(storedPassword, inputPassword.Trim(), StringComparison.Ordinal);
        }

        try
        {
            using var client = new HttpClient
            {
                Timeout = TimeSpan.FromSeconds(10)
            };

            var payload = JsonSerializer.Serialize(new
            {
                email,
                password = inputPassword.Trim()
            });
            using var content = new StringContent(payload, Encoding.UTF8, "application/json");
            using var response = client.PostAsync("http://localhost/tunicover/php/auth/login.php", content).GetAwaiter().GetResult();

            if (!response.IsSuccessStatusCode)
            {
                return false;
            }

            var responseBody = response.Content.ReadAsStringAsync().GetAwaiter().GetResult();
            using var document = JsonDocument.Parse(responseBody);
            return document.RootElement.TryGetProperty("success", out var successElement) && successElement.GetBoolean();
        }
        catch
        {
            return false;
        }
    }
}
