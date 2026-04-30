using System.Data;
using MySql.Data.MySqlClient;
using TunCoverDesktopManager.Data;
using TunCoverDesktopManager.Models;

namespace TunCoverDesktopManager.Repositories;

public sealed class ActivityRepository
{
    private readonly DbManager _dbManager = new();

    public DataTable Search(string keyword)
    {
        using var connection = _dbManager.CreateConnection();
        connection.Open();

        const string sql = @"
            SELECT
                a.id_activite AS Id,
                a.titre AS Titre,
                a.ville AS Ville,
                a.prix AS Prix,
                COALESCE(a.capacite, a.max_participants, 0) AS Capacite,
                COALESCE(a.places_reservees, 0) AS Reservees,
                GREATEST(COALESCE(a.capacite, a.max_participants, 0) - COALESCE(a.places_reservees, 0), 0) AS Restantes,
                a.statut AS StatutPublication,
                COALESCE(a.availability_status, 'disponible') AS Disponibilite
            FROM activites a
            WHERE a.titre LIKE @kw OR a.ville LIKE @kw
            ORDER BY a.date_creation DESC;";

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

        const string sql = "SELECT id_activite, titre FROM activites ORDER BY titre;";
        using var command = new MySqlCommand(sql, connection);
        using var reader = command.ExecuteReader();

        var items = new List<KeyValuePair<int, string>>();
        while (reader.Read())
        {
            items.Add(new KeyValuePair<int, string>(reader.GetInt32("id_activite"), reader.GetString("titre")));
        }

        return items;
    }

    public Activity? GetById(int id)
    {
        using var connection = _dbManager.CreateConnection();
        connection.Open();

        const string sql = @"
            SELECT
                id_activite, id_artisan, titre, description, prix, ville, duree_heures,
                COALESCE(capacite, max_participants, 0) AS capacite,
                COALESCE(places_reservees, 0) AS places_reservees,
                GREATEST(COALESCE(capacite, max_participants, 0) - COALESCE(places_reservees, 0), 0) AS places_restantes,
                statut, COALESCE(availability_status, 'disponible') AS availability_status
            FROM activites
            WHERE id_activite = @id
            LIMIT 1;";

        using var command = new MySqlCommand(sql, connection);
        command.Parameters.AddWithValue("@id", id);
        using var reader = command.ExecuteReader();

        if (!reader.Read()) return null;

        return new Activity
        {
            Id = reader.GetInt32("id_activite"),
            ArtisanId = reader.GetInt32("id_artisan"),
            Title = reader.GetString("titre"),
            Description = reader.GetString("description"),
            Price = reader.GetDecimal("prix"),
            City = reader.GetString("ville"),
            DurationHours = reader.GetInt32("duree_heures"),
            Capacity = reader.GetInt32("capacite"),
            ReservedPlaces = reader.GetInt32("places_reservees"),
            RemainingPlaces = reader.GetInt32("places_restantes"),
            PublishStatus = reader.GetString("statut"),
            AvailabilityStatus = reader.GetString("availability_status")
        };
    }

    public void Create(Activity activity)
    {
        using var connection = _dbManager.CreateConnection();
        connection.Open();

        const string sql = @"
            INSERT INTO activites
            (id_artisan, titre, description, prix, ville, duree_heures, max_participants, capacite, places_reservees, availability_status, statut)
            VALUES
            (@artisanId, @title, @description, @price, @city, @duration, @capacity, @capacity, 0, 'disponible', @status);";

        using var command = new MySqlCommand(sql, connection);
        command.Parameters.AddWithValue("@artisanId", activity.ArtisanId);
        command.Parameters.AddWithValue("@title", activity.Title);
        command.Parameters.AddWithValue("@description", activity.Description);
        command.Parameters.AddWithValue("@price", activity.Price);
        command.Parameters.AddWithValue("@city", activity.City);
        command.Parameters.AddWithValue("@duration", activity.DurationHours);
        command.Parameters.AddWithValue("@capacity", activity.Capacity);
        command.Parameters.AddWithValue("@status", activity.PublishStatus);
        command.ExecuteNonQuery();
    }

    public void Update(Activity activity)
    {
        using var connection = _dbManager.CreateConnection();
        connection.Open();

        const string sql = @"
            UPDATE activites
            SET titre = @title,
                description = @description,
                prix = @price,
                ville = @city,
                duree_heures = @duration,
                max_participants = @capacity,
                capacite = @capacity,
                statut = @status
            WHERE id_activite = @id;";

        using var command = new MySqlCommand(sql, connection);
        command.Parameters.AddWithValue("@id", activity.Id);
        command.Parameters.AddWithValue("@title", activity.Title);
        command.Parameters.AddWithValue("@description", activity.Description);
        command.Parameters.AddWithValue("@price", activity.Price);
        command.Parameters.AddWithValue("@city", activity.City);
        command.Parameters.AddWithValue("@duration", activity.DurationHours);
        command.Parameters.AddWithValue("@capacity", activity.Capacity);
        command.Parameters.AddWithValue("@status", activity.PublishStatus);
        command.ExecuteNonQuery();
    }

    public void Delete(int id)
    {
        using var connection = _dbManager.CreateConnection();
        connection.Open();

        const string sql = "UPDATE activites SET statut = 'supprime' WHERE id_activite = @id;";
        using var command = new MySqlCommand(sql, connection);
        command.Parameters.AddWithValue("@id", id);
        command.ExecuteNonQuery();
    }

    public void SetStatus(int id, string status)
    {
        using var connection = _dbManager.CreateConnection();
        connection.Open();

        const string sql = "UPDATE activites SET statut = @status WHERE id_activite = @id;";
        using var command = new MySqlCommand(sql, connection);
        command.Parameters.AddWithValue("@status", status);
        command.Parameters.AddWithValue("@id", id);
        command.ExecuteNonQuery();
    }
}
