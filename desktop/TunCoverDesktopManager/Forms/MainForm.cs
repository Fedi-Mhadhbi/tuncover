using TunCoverDesktopManager.Repositories;
using TunCoverDesktopManager.Services;
using TunCoverDesktopManager.Utils;

namespace TunCoverDesktopManager.Forms;

public sealed class MainForm : Form
{
    private readonly DashboardRepository _dashboardRepository = new();

    public MainForm()
    {
        var currentUser = SessionContext.CurrentUser;
        Text = "TunCover Desktop - Main Menu";
        StartPosition = FormStartPosition.CenterScreen;
        MinimumSize = new Size(1200, 760);
        BackColor = UiTheme.Cream;

        var roleText = currentUser is null
            ? "No active user"
            : $"Connected: {currentUser.FullName} ({currentUser.Role})";

        var hero = UiTheme.CreateHeroPanel(
            "TunCover\nDesktop Studio",
            roleText,
            "Monitor reservations, revenue, losses and moderation actions from one branded admin space inspired by your public TunCover website.");
        hero.Dock = DockStyle.Top;
        hero.Height = 255;

        var body = new FlowLayoutPanel
        {
            Dock = DockStyle.Fill,
            FlowDirection = FlowDirection.TopDown,
            WrapContents = false,
            AutoScroll = true,
            Padding = new Padding(24),
            BackColor = UiTheme.Sand
        };

        body.Controls.Add(BuildStatsSection());
        body.Controls.Add(BuildSectionTitle("Management Spaces", "Choose one area to consult, create, modify, and organize your data."));
        body.Controls.Add(BuildManagementGrid(currentUser?.Role.Equals("admin", StringComparison.OrdinalIgnoreCase) == true));

        Controls.Add(body);
        Controls.Add(hero);
    }

    private Control BuildStatsSection()
    {
        var stats = _dashboardRepository.GetStats();
        var container = new Panel
        {
            Width = 1100,
            Height = 190,
            Margin = new Padding(0, 0, 0, 14)
        };

        var title = UiTheme.CreateSectionTitle("Platform Snapshot");
        title.Location = new Point(0, 0);

        var subtitle = new Label
        {
            AutoSize = true,
            Text = "Live admin indicators for reservations, revenue, refunds and active users.",
            Font = new Font("Segoe UI", 10),
            ForeColor = UiTheme.TextMuted,
            Location = new Point(0, 38)
        };

        var cards = new FlowLayoutPanel
        {
            Location = new Point(0, 76),
            Width = 1100,
            Height = 100,
            WrapContents = false
        };

        cards.Controls.Add(CreateStatCard("Reservations", stats.TotalReservations.ToString(), $"{stats.ConfirmedReservations} confirmed"));
        cards.Controls.Add(CreateStatCard("Month Revenue", $"{stats.MonthRevenue:0.00} TND", "Paid transactions this month"));
        cards.Controls.Add(CreateStatCard("Refunded", $"{stats.RefundedAmount:0.00} TND", "Returned to tourists"));
        cards.Controls.Add(CreateStatCard("Loss / Cancelled", $"{stats.EstimatedLoss:0.00} TND", $"{stats.CancelledReservations} cancelled"));
        cards.Controls.Add(CreateStatCard("Active Users", stats.ActiveUsers.ToString(), "Currently active accounts"));

        container.Controls.Add(title);
        container.Controls.Add(subtitle);
        container.Controls.Add(cards);
        return container;
    }

    private static Panel CreateStatCard(string title, string value, string caption)
    {
        var card = UiTheme.CreateCard();
        card.Size = new Size(205, 88);
        card.Margin = new Padding(0, 0, 14, 0);

        var accent = new Panel
        {
            Width = 58,
            Height = 5,
            BackColor = UiTheme.Clay,
            Margin = new Padding(0, 0, 0, 8)
        };

        var titleLabel = new Label
        {
            AutoSize = true,
            Text = title,
            Font = new Font("Segoe UI", 9, FontStyle.Bold),
            ForeColor = UiTheme.TextMuted
        };

        var valueLabel = new Label
        {
            AutoSize = true,
            Text = value,
            Font = new Font("Georgia", 16, FontStyle.Regular),
            ForeColor = UiTheme.Ink
        };

        var captionLabel = new Label
        {
            AutoSize = true,
            Text = caption,
            Font = new Font("Segoe UI", 8.5f),
            ForeColor = UiTheme.TextMuted
        };

        var layout = new FlowLayoutPanel
        {
            Dock = DockStyle.Fill,
            FlowDirection = FlowDirection.TopDown,
            WrapContents = false
        };
        layout.Controls.Add(accent);
        layout.Controls.Add(titleLabel);
        layout.Controls.Add(valueLabel);
        layout.Controls.Add(captionLabel);
        card.Controls.Add(layout);
        return card;
    }

    private static Control BuildSectionTitle(string titleText, string subtitleText)
    {
        var panel = new Panel
        {
            Width = 1100,
            Height = 74,
            Margin = new Padding(0, 0, 0, 8)
        };

        var title = UiTheme.CreateSectionTitle(titleText);
        title.Location = new Point(0, 0);

        var subtitle = new Label
        {
            AutoSize = true,
            Text = subtitleText,
            Font = new Font("Segoe UI", 10),
            ForeColor = UiTheme.TextMuted,
            Location = new Point(0, 38)
        };

        panel.Controls.Add(title);
        panel.Controls.Add(subtitle);
        return panel;
    }

    private static Control BuildManagementGrid(bool isAdmin)
    {
        var grid = new TableLayoutPanel
        {
            Width = 1100,
            Height = 300,
            ColumnCount = isAdmin ? 4 : 3,
            RowCount = 1,
            Margin = new Padding(0, 0, 0, 16)
        };

        for (var i = 0; i < grid.ColumnCount; i++)
        {
            grid.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 100f / grid.ColumnCount));
        }

        var col = 0;
        if (isAdmin)
        {
            grid.Controls.Add(CreateFeatureCard(
                "Manage Users",
                "Ban, reactivate, delete and supervise platform accounts.",
                owner => new UsersListForm().ShowDialog(owner)), col++, 0);
        }

        grid.Controls.Add(CreateFeatureCard(
            "Manage Activities",
            "Publish, pause, review or delete artisan experiences.",
            owner => new ActivitiesListForm().ShowDialog(owner)), col++, 0);

        grid.Controls.Add(CreateFeatureCard(
            "Manage Reservations",
            "Confirm, complete, cancel or refund bookings with payment visibility.",
            owner => new ReservationsListForm().ShowDialog(owner)), col++, 0);

        grid.Controls.Add(CreateFeatureCard(
            "Logout",
            "Close the current session and return to the desktop login screen.",
            owner => owner.Close()), col, 0);

        return grid;
    }

    private static Panel CreateFeatureCard(string title, string description, Action<Form> action)
    {
        var card = UiTheme.CreateCard();
        card.Dock = DockStyle.Fill;
        card.Margin = new Padding(0, 0, 18, 0);

        var accent = new Panel
        {
            Width = 76,
            Height = 6,
            BackColor = UiTheme.Clay,
            Margin = new Padding(0, 0, 0, 14)
        };

        var titleLabel = new Label
        {
            AutoSize = false,
            Width = 220,
            Height = 74,
            Text = title,
            Font = new Font("Georgia", 17, FontStyle.Regular),
            ForeColor = UiTheme.Ink
        };

        var descriptionLabel = new Label
        {
            AutoSize = false,
            Width = 220,
            Height = 74,
            Text = description,
            Font = new Font("Segoe UI", 10),
            ForeColor = UiTheme.TextMuted
        };

        var openButton = UiTheme.CreatePrimaryButton("Open");
        openButton.Click += (_, _) =>
        {
            if (openButton.FindForm() is Form owner)
            {
                action(owner);
            }
        };

        var layout = new FlowLayoutPanel
        {
            Dock = DockStyle.Fill,
            FlowDirection = FlowDirection.TopDown,
            WrapContents = false
        };
        layout.Controls.Add(accent);
        layout.Controls.Add(titleLabel);
        layout.Controls.Add(descriptionLabel);
        layout.Controls.Add(openButton);
        card.Controls.Add(layout);
        return card;
    }
}
