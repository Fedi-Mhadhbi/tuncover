namespace TunCoverDesktopManager.Models;

public sealed class DashboardStats
{
    public int TotalReservations { get; set; }
    public int ConfirmedReservations { get; set; }
    public int CancelledReservations { get; set; }
    public decimal MonthRevenue { get; set; }
    public decimal RefundedAmount { get; set; }
    public decimal EstimatedLoss { get; set; }
    public int ActiveUsers { get; set; }
}
