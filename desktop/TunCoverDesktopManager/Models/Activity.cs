namespace TunCoverDesktopManager.Models;

public sealed class Activity
{
    public int Id { get; set; }
    public int ArtisanId { get; set; }
    public string Title { get; set; } = string.Empty;
    public string Description { get; set; } = string.Empty;
    public decimal Price { get; set; }
    public string City { get; set; } = string.Empty;
    public int DurationHours { get; set; }
    public int Capacity { get; set; }
    public int ReservedPlaces { get; set; }
    public int RemainingPlaces { get; set; }
    public string PublishStatus { get; set; } = string.Empty;
    public string AvailabilityStatus { get; set; } = string.Empty;
    public string CategoryName { get; set; } = string.Empty;
}
