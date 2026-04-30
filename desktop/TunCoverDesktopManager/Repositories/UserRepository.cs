using System.Data;
using MySql.Data.MySqlClient;
using TunCoverDesktopManager.Data;
using TunCoverDesktopManager.Models;

namespace TunCoverDesktopManager.Repositories;

public sealed class UserRepository
{
    private readonly DbManager _dbManager = new();

    public UserRepository()
    {
        EnsureSchema();
    }

    public DataTable Search(string keyword)
    {
        using var connection = _dbManager.CreateConnection();
        connection.Open();

        const string sql = @"
            SELECT id_utilisateur AS Id, prenom AS Prenom, nom AS Nom, email AS Email, role AS Role, statut AS Statut,
                   COALESCE(DATE_FORMAT(ban_until, '%Y-%m-%d %H:%i'), '-') AS BanUntil
            FROM utilisateurs
            WHERE nom LIKE @kw OR prenom LIKE @kw OR email LIKE @kw
            ORDER BY date_inscription DESC;";

        using var command = new MySqlCommand(sql, connection);
        command.Parameters.AddWithValue("@kw", $"%{keyword.Trim()}%");

        using var adapter = new MySqlDataAdapter(command);
        var table = new DataTable();
        adapter.Fill(table);
        return table;
    }

    public List<KeyValuePair<int, string>> GetAllForDropdown()
    {
        using var connection = _dbManager.CreateConnection();
        connection.Open();

        const string sql = @"
            SELECT id_utilisateur, CONCAT(prenom, ' ', nom) AS full_name
            FROM utilisateurs
            ORDER BY prenom, nom;";

        using var command = new MySqlCommand(sql, connection);
        using var reader = command.ExecuteReader();

        var items = new List<KeyValuePair<int, string>>();
        while (reader.Read())
        {
            items.Add(new KeyValuePair<int, string>(
                reader.GetInt32("id_utilisateur"),
                reader.GetString("full_name")));
        }

        return items;
    }

    public List<KeyValuePair<int, string>> GetAllByRoleForDropdown(string role)
    {
        using var connection = _dbManager.CreateConnection();
        connection.Open();

        const string sql = @"
            SELECT id_utilisateur, CONCAT(prenom, ' ', nom) AS full_name
            FROM utilisateurs
            WHERE role = @role
            ORDER BY prenom, nom;";

        using var command = new MySqlCommand(sql, connection);
        command.Parameters.AddWithValue("@role", role);
        using var reader = command.ExecuteReader();

        var items = new List<KeyValuePair<int, string>>();
        while (reader.Read())
        {
            items.Add(new KeyValuePair<int, string>(
                reader.GetInt32("id_utilisateur"),
                reader.GetString("full_name")));
        }

        return items;
    }

    public User? GetById(int id)
    {
        using var connection = _dbManager.CreateConnection();
        connection.Open();

        const string sql = @"
            SELECT id_utilisateur, nom, prenom, email, role, statut, COALESCE(telephone, '') AS telephone,
                   date_inscription, ban_until
            FROM utilisateurs
            WHERE id_utilisateur = @id
            LIMIT 1;";

        using var command = new MySqlCommand(sql, connection);
        command.Parameters.AddWithValue("@id", id);
        using var reader = command.ExecuteReader();

        if (!reader.Read()) return null;

        return new User
        {
            Id = reader.GetInt32("id_utilisateur"),
            Nom = reader.GetString("nom"),
            Prenom = reader.GetString("prenom"),
            Email = reader.GetString("email"),
            Role = reader.GetString("role"),
            Statut = reader.GetString("statut"),
            Telephone = reader.GetString("telephone"),
            DateInscription = reader.GetDateTime("date_inscription"),
            BanUntil = reader.IsDBNull("ban_until") ? null : reader.GetDateTime("ban_until")
        };
    }

    public void Create(User user)
    {
        using var connection = _dbManager.CreateConnection();
        connection.Open();

        const string sql = @"
            INSERT INTO utilisateurs (nom, prenom, email, role, statut, telephone, mot_de_passe, date_inscription)
            VALUES (@nom, @prenom, @email, @role, @statut, @telephone, @password, NOW());";

        using var command = new MySqlCommand(sql, connection);
        command.Parameters.AddWithValue("@nom", user.Nom);
        command.Parameters.AddWithValue("@prenom", user.Prenom);
        command.Parameters.AddWithValue("@email", user.Email);
        command.Parameters.AddWithValue("@role", user.Role);
        command.Parameters.AddWithValue("@statut", user.Statut);
        command.Parameters.AddWithValue("@telephone", user.Telephone);
        command.Parameters.AddWithValue("@password", "desktop_demo");
        command.ExecuteNonQuery();
    }

    public void Update(User user)
    {
        using var connection = _dbManager.CreateConnection();
        connection.Open();

        const string sql = @"
            UPDATE utilisateurs
            SET nom = @nom, prenom = @prenom, email = @email, role = @role, statut = @statut, telephone = @telephone
            WHERE id_utilisateur = @id;";

        using var command = new MySqlCommand(sql, connection);
        command.Parameters.AddWithValue("@id", user.Id);
        command.Parameters.AddWithValue("@nom", user.Nom);
        command.Parameters.AddWithValue("@prenom", user.Prenom);
        command.Parameters.AddWithValue("@email", user.Email);
        command.Parameters.AddWithValue("@role", user.Role);
        command.Parameters.AddWithValue("@statut", user.Statut);
        command.Parameters.AddWithValue("@telephone", user.Telephone);
        command.ExecuteNonQuery();
    }

    public void Delete(int id)
    {
        using var connection = _dbManager.CreateConnection();
        connection.Open();

        const string sql = "DELETE FROM utilisateurs WHERE id_utilisateur = @id;";
        using var command = new MySqlCommand(sql, connection);
        command.Parameters.AddWithValue("@id", id);
        command.ExecuteNonQuery();
    }

    public void SetStatus(int id, string status)
    {
        using var connection = _dbManager.CreateConnection();
        connection.Open();

        const string sql = "UPDATE utilisateurs SET statut = @status WHERE id_utilisateur = @id;";
        using var command = new MySqlCommand(sql, connection);
        command.Parameters.AddWithValue("@status", status);
        command.Parameters.AddWithValue("@id", id);
        command.ExecuteNonQuery();
    }

    public void BanUntil(int id, DateTime until)
    {
        using var connection = _dbManager.CreateConnection();
        connection.Open();

        const string sql = "UPDATE utilisateurs SET statut = 'banni', ban_until = @until WHERE id_utilisateur = @id;";
        using var command = new MySqlCommand(sql, connection);
        command.Parameters.AddWithValue("@until", until);
        command.Parameters.AddWithValue("@id", id);
        command.ExecuteNonQuery();
    }

    public void ClearBan(int id)
    {
        using var connection = _dbManager.CreateConnection();
        connection.Open();

        const string sql = "UPDATE utilisateurs SET statut = 'actif', ban_until = NULL WHERE id_utilisateur = @id;";
        using var command = new MySqlCommand(sql, connection);
        command.Parameters.AddWithValue("@id", id);
        command.ExecuteNonQuery();
    }

    private void EnsureSchema()
    {
        using var connection = _dbManager.CreateConnection();
        connection.Open();

        const string sql = @"
            ALTER TABLE utilisateurs
            ADD COLUMN IF NOT EXISTS ban_until DATETIME NULL AFTER statut;";

        using var command = new MySqlCommand(sql, connection);
        command.ExecuteNonQuery();
    }
}
