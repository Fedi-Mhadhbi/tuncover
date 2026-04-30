using TunCoverDesktopManager.Models;

namespace TunCoverDesktopManager.Services;

public static class SessionContext
{
    public static User? CurrentUser { get; private set; }

    public static void SetUser(User user)
    {
        CurrentUser = user;
    }

    public static void Clear()
    {
        CurrentUser = null;
    }
}
