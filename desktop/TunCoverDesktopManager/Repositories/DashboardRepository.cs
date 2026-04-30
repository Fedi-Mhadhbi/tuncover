using MySql.Data.MySqlClient;
using TunCoverDesktopManager.Data;
using TunCoverDesktopManager.Models;

namespace TunCoverDesktopManager.Repositories;

public sealed class DashboardRepository
{
    private readonly DbManager _dbManager = new();

    public DashboardStats GetStats()
    {
        using var connection = _dbManager.CreateConnection();
        connection.Open();

        return new DashboardStats
        {
            TotalReservations = ExecuteInt(connection, "SELECT COUNT(*) FROM reservations;"),
            ConfirmedReservations = ExecuteInt(connection, "SELECT COUNT(*) FROM reservations WHERE statut = 'confirmee';"),
            CancelledReservations = ExecuteInt(connection, "SELECT COUNT(*) FROM reservations WHERE statut = 'annulee';"),
            MonthRevenue = ExecuteDecimal(connection, "SELECT COALESCE(SUM(amount),0) FROM payments WHERE payment_status='completed' AND MONTH(paid_at)=MONTH(NOW()) AND YEAR(paid_at)=YEAR(NOW());"),
            RefundedAmount = ExecuteDecimal(connection, "SELECT COALESCE(SUM(amount),0) FROM payments WHERE payment_status='refunded';"),
            EstimatedLoss = ExecuteDecimal(connection, "SELECT COALESCE(SUM(montant_total),0) FROM reservations WHERE statut='annulee';"),
            ActiveUsers = ExecuteInt(connection, "SELECT COUNT(*) FROM utilisateurs WHERE statut='actif';")
        };
    }

    private static int ExecuteInt(MySqlConnection connection, string sql)
    {
        using var command = new MySqlCommand(sql, connection);
        return Convert.ToInt32(command.ExecuteScalar() ?? 0);
    }

    private static decimal ExecuteDecimal(MySqlConnection connection, string sql)
    {
        using var command = new MySqlCommand(sql, connection);
        return Convert.ToDecimal(command.ExecuteScalar() ?? 0m);
    }
}
