namespace TunCoverDesktopManager.Models;

public sealed class Reservation
{
    public int Id { get; set; }
    public int ActivityId { get; set; }
    public int TouristId { get; set; }
    public string ActivityTitle { get; set; } = string.Empty;
    public string TouristName { get; set; } = string.Empty;
    public DateTime ReservationDate { get; set; }
    public DateTime ActivityDate { get; set; }
    public string ActivityTime { get; set; } = string.Empty;
    public int Participants { get; set; }
    public decimal TotalAmount { get; set; }
    public string Status { get; set; } = string.Empty;
    public string PaymentStatus { get; set; } = string.Empty;
}
