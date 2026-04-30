using MySql.Data.MySqlClient;
using TunCoverDesktopManager.Config;

namespace TunCoverDesktopManager.Data;

public sealed class DbManager
{
    public MySqlConnection CreateConnection()
    {
        return new MySqlConnection(DbConfig.ConnectionString);
    }
}
