namespace TunCoverDesktopManager.Models;

public sealed class User
{
    public int Id { get; set; }
    public string Nom { get; set; } = string.Empty;
    public string Prenom { get; set; } = string.Empty;
    public string Email { get; set; } = string.Empty;
    public string Role { get; set; } = string.Empty;
    public string Statut { get; set; } = string.Empty;
    public string Telephone { get; set; } = string.Empty;
    public DateTime DateInscription { get; set; }
    public DateTime? BanUntil { get; set; }

    public string FullName => $"{Prenom} {Nom}".Trim();
}
