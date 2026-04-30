using System.Data;
using MySql.Data.MySqlClient;
using TunCoverDesktopManager.Data;
using TunCoverDesktopManager.Models;

namespace TunCoverDesktopManager.Repositories;

public sealed class ReservationRepository
{
    private readonly DbManager _dbManager = new();

    public DataTable Search(string keyword)
    {
        using var connection = _dbManager.CreateConnection();
        connection.Open();

        const string sql = @"
            SELECT
                r.id_reservation AS Id,
                a.titre AS Activite,
                CONCAT(u.prenom, ' ', u.nom) AS Touriste,
                r.date_activite AS DateActivite,
                r.nb_participants AS Participants,
                r.montant_total AS Montant,
                r.statut AS Statut,
                COALESCE(p.payment_status, 'pending') AS Paiement
            FROM reservations r
            JOIN activites a ON a.id_activite = r.id_activite
            JOIN utilisateurs u ON u.id_utilisateur = r.id_touriste
            LEFT JOIN payments p ON p.id_reservation = r.id_reservation
            WHERE a.titre LIKE @kw OR u.nom LIKE @kw OR u.prenom LIKE @kw
            ORDER BY r.date_reservation DESC;";

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
            SELECT r.id_reservation, CONCAT(a.titre, ' - ', u.prenom, ' ', u.nom) AS label
            FROM reservations r
            JOIN activites a ON a.id_activite = r.id_activite
            JOIN utilisateurs u ON u.id_utilisateur = r.id_touriste
            ORDER BY r.id_reservation DESC;";

        using var command = new MySqlCommand(sql, connection);
        using var reader = command.ExecuteReader();

        var items = new List<KeyValuePair<int, string>>();
        while (reader.Read())
        {
            items.Add(new KeyValuePair<int, string>(reader.GetInt32("id_reservation"), reader.GetString("label")));
        }

        return items;
    }

    public Reservation? GetById(int id)
    {
        using var connection = _dbManager.CreateConnection();
        connection.Open();

        const string sql = @"
            SELECT
                r.id_reservation, r.id_activite, r.id_touriste, r.date_reservation, r.date_activite, r.heure_activite,
                r.nb_participants, r.montant_total, r.statut,
                a.titre AS activite_titre,
                CONCAT(u.prenom, ' ', u.nom) AS tourist_name,
                COALESCE(p.payment_status, 'pending') AS payment_status
            FROM reservations r
            JOIN activites a ON a.id_activite = r.id_activite
            JOIN utilisateurs u ON u.id_utilisateur = r.id_touriste
            LEFT JOIN payments p ON p.id_reservation = r.id_reservation
            WHERE r.id_reservation = @id
            LIMIT 1;";

        using var command = new MySqlCommand(sql, connection);
        command.Parameters.AddWithValue("@id", id);
        using var reader = command.ExecuteReader();

        if (!reader.Read()) return null;

        return new Reservation
        {
            Id = reader.GetInt32("id_reservation"),
            ActivityId = reader.GetInt32("id_activite"),
            TouristId = reader.GetInt32("id_touriste"),
            ReservationDate = reader.GetDateTime("date_reservation"),
            ActivityDate = reader.GetDateTime("date_activite"),
            ActivityTime = reader.GetString("heure_activite"),
            Participants = reader.GetInt32("nb_participants"),
            TotalAmount = reader.GetDecimal("montant_total"),
            Status = reader.GetString("statut"),
            ActivityTitle = reader.GetString("activite_titre"),
            TouristName = reader.GetString("tourist_name"),
            PaymentStatus = reader.GetString("payment_status")
        };
    }

    public void Update(Reservation reservation)
    {
        using var connection = _dbManager.CreateConnection();
        connection.Open();

        const string sql = @"
            UPDATE reservations
            SET date_activite = @dateActivite,
                heure_activite = @heureActivite,
                nb_participants = @participants,
                statut = @statut
            WHERE id_reservation = @id;";

        using var command = new MySqlCommand(sql, connection);
        command.Parameters.AddWithValue("@id", reservation.Id);
        command.Parameters.AddWithValue("@dateActivite", reservation.ActivityDate);
        command.Parameters.AddWithValue("@heureActivite", reservation.ActivityTime);
        command.Parameters.AddWithValue("@participants", reservation.Participants);
        command.Parameters.AddWithValue("@statut", reservation.Status);
        command.ExecuteNonQuery();
    }

    public void Create(Reservation reservation)
    {
        using var connection = _dbManager.CreateConnection();
        connection.Open();

        const string sql = @"
            INSERT INTO reservations
            (id_activite, id_touriste, date_reservation, date_activite, heure_activite, nb_participants, montant_total, statut)
            VALUES
            (@activityId, @touristId, NOW(), @activityDate, @activityTime, @participants, @totalAmount, @status);";

        using var command = new MySqlCommand(sql, connection);
        command.Parameters.AddWithValue("@activityId", reservation.ActivityId);
        command.Parameters.AddWithValue("@touristId", reservation.TouristId);
        command.Parameters.AddWithValue("@activityDate", reservation.ActivityDate);
        command.Parameters.AddWithValue("@activityTime", reservation.ActivityTime);
        command.Parameters.AddWithValue("@participants", reservation.Participants);
        command.Parameters.AddWithValue("@totalAmount", reservation.TotalAmount);
        command.Parameters.AddWithValue("@status", reservation.Status);
        command.ExecuteNonQuery();
    }

    public void Delete(int id)
    {
        using var connection = _dbManager.CreateConnection();
        connection.Open();

        const string sql = "DELETE FROM reservations WHERE id_reservation = @id;";
        using var command = new MySqlCommand(sql, connection);
        command.Parameters.AddWithValue("@id", id);
        command.ExecuteNonQuery();
    }

    public void SetStatus(int id, string status)
    {
        using var connection = _dbManager.CreateConnection();
        connection.Open();
        using var transaction = connection.BeginTransaction();

        var reservation = GetReservationForAction(connection, transaction, id);
        if (reservation is null)
        {
            throw new InvalidOperationException("Reservation not found.");
        }

        const string updateSql = "UPDATE reservations SET statut = @status WHERE id_reservation = @id;";
        using (var update = new MySqlCommand(updateSql, connection, transaction))
        {
            update.Parameters.AddWithValue("@status", status);
            update.Parameters.AddWithValue("@id", id);
            update.ExecuteNonQuery();
        }

        if (status == "annulee" && reservation.PaymentStatus == "completed")
        {
            UpdatePaymentStatus(connection, transaction, id, "refunded");
        }

        SyncActivityCapacity(connection, transaction, reservation.ActivityId);
        transaction.Commit();
    }

    public void Refund(int id)
    {
        using var connection = _dbManager.CreateConnection();
        connection.Open();

        using var transaction = connection.BeginTransaction();
        var reservation = GetReservationForAction(connection, transaction, id);
        if (reservation is null)
        {
            throw new InvalidOperationException("Reservation not found.");
        }

        if (!string.Equals(reservation.PaymentStatus, "completed", StringComparison.OrdinalIgnoreCase))
        {
            throw new InvalidOperationException("Only paid reservations can be refunded.");
        }

        UpdatePaymentStatus(connection, transaction, id, "refunded");
        transaction.Commit();
    }

    private static Reservation? GetReservationForAction(MySqlConnection connection, MySqlTransaction transaction, int id)
    {
        const string sql = @"
            SELECT r.id_reservation, r.id_activite, r.statut, COALESCE(p.payment_status, 'pending') AS payment_status
            FROM reservations r
            LEFT JOIN payments p ON p.id_reservation = r.id_reservation
            WHERE r.id_reservation = @id
            LIMIT 1;";

        using var command = new MySqlCommand(sql, connection, transaction);
        command.Parameters.AddWithValue("@id", id);
        using var reader = command.ExecuteReader();
        if (!reader.Read())
        {
            return null;
        }

        return new Reservation
        {
            Id = reader.GetInt32("id_reservation"),
            ActivityId = reader.GetInt32("id_activite"),
            Status = reader.GetString("statut"),
            PaymentStatus = reader.GetString("payment_status")
        };
    }

    private static void UpdatePaymentStatus(MySqlConnection connection, MySqlTransaction transaction, int reservationId, string status)
    {
        const string sql = @"
            UPDATE payments
            SET payment_status = @status,
                updated_at = NOW()
            WHERE id_reservation = @id;";

        using var command = new MySqlCommand(sql, connection, transaction);
        command.Parameters.AddWithValue("@status", status);
        command.Parameters.AddWithValue("@id", reservationId);
        command.ExecuteNonQuery();
    }

    private static void SyncActivityCapacity(MySqlConnection connection, MySqlTransaction transaction, int activityId)
    {
        const string sql = @"
            UPDATE activites a
            LEFT JOIN (
                SELECT id_activite, COALESCE(SUM(nb_participants), 0) AS reserved_count
                FROM reservations
                WHERE id_activite = @id AND statut = 'confirmee'
                GROUP BY id_activite
            ) r ON r.id_activite = a.id_activite
            SET a.places_reservees = COALESCE(r.reserved_count, 0),
                a.availability_status = CASE
                    WHEN GREATEST(COALESCE(a.capacite, a.max_participants, 0) - COALESCE(r.reserved_count, 0), 0) = 0
                        THEN 'complet'
                    ELSE 'disponible'
                END
            WHERE a.id_activite = @id;";

        using var command = new MySqlCommand(sql, connection, transaction);
        command.Parameters.AddWithValue("@id", activityId);
        command.ExecuteNonQuery();
    }
}
