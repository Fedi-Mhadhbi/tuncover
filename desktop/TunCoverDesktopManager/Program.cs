using TunCoverDesktopManager.Forms;
using TunCoverDesktopManager.Services;

namespace TunCoverDesktopManager;

internal static class Program
{
    [STAThread]
    private static void Main()
    {
        ApplicationConfiguration.Initialize();
        SessionContext.Clear();
        Application.Run(new LoginForm());
    }
}
