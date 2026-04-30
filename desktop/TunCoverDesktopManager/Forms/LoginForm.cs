using TunCoverDesktopManager.Repositories;
using TunCoverDesktopManager.Services;
using TunCoverDesktopManager.Utils;

namespace TunCoverDesktopManager.Forms;

public sealed class LoginForm : Form
{
    private readonly AuthRepository _authRepository = new();
    private readonly TextBox _emailTextBox = new() { Width = 320, PlaceholderText = "Email" };
    private readonly TextBox _passwordTextBox = new() { Width = 320, UseSystemPasswordChar = true, PlaceholderText = "Password" };
    private readonly Label _hintLabel = new()
    {
        AutoSize = true,
        ForeColor = UiTheme.TextMuted,
        Text = "Sign in with the same account you use on the TunCover website."
    };

    public LoginForm()
    {
        Text = "TunCover Desktop - Login";
        StartPosition = FormStartPosition.CenterScreen;
        MinimumSize = new Size(1040, 640);
        BackColor = UiTheme.Cream;

        UiTheme.StyleTextInput(_emailTextBox);
        UiTheme.StyleTextInput(_passwordTextBox);

        var hero = UiTheme.CreateHeroPanel(
            "Discover\nHidden Tunisia",
            "TunCover Desktop Manager",
            "A creative desktop space for your admin and artisan operations, inspired by the same warm visual identity as the TunCover website.");

        var card = UiTheme.CreateCard();
        card.Dock = DockStyle.Fill;
        card.Padding = new Padding(32);

        var titleLabel = UiTheme.CreateSectionTitle("Welcome Back");
        var subtitleLabel = new Label
        {
            AutoSize = true,
            Text = "Manage activities, artisans and reservations through one elegant control center.",
            Font = new Font("Segoe UI", 10),
            ForeColor = UiTheme.TextMuted
        };

        var infoChip = new Label
        {
            AutoSize = true,
            Text = "Secure desktop access",
            Font = new Font("Segoe UI", 9, FontStyle.Bold),
            ForeColor = UiTheme.Clay,
            BackColor = Color.FromArgb(252, 244, 236),
            Padding = new Padding(10, 6, 10, 6)
        };
        UiTheme.MakeRounded(infoChip, 16);

        var loginButton = UiTheme.CreatePrimaryButton("Sign In");
        loginButton.Click += (_, _) => HandleLogin();

        var exitButton = UiTheme.CreateSecondaryButton("Exit");
        exitButton.Click += (_, _) => Close();

        var buttons = new FlowLayoutPanel
        {
            FlowDirection = FlowDirection.LeftToRight,
            AutoSize = true,
            WrapContents = false
        };
        buttons.Controls.Add(loginButton);
        buttons.Controls.Add(exitButton);

        var cardLayout = new FlowLayoutPanel
        {
            Dock = DockStyle.Fill,
            FlowDirection = FlowDirection.TopDown,
            WrapContents = false,
            AutoScroll = true
        };
        cardLayout.Controls.Add(infoChip);
        cardLayout.Controls.Add(titleLabel);
        cardLayout.Controls.Add(subtitleLabel);
        cardLayout.Controls.Add(new Label { AutoSize = true, Height = 12 });
        cardLayout.Controls.Add(_emailTextBox);
        cardLayout.Controls.Add(_passwordTextBox);
        cardLayout.Controls.Add(_hintLabel);
        cardLayout.Controls.Add(new Label { AutoSize = true, Height = 12 });
        cardLayout.Controls.Add(buttons);
        card.Controls.Add(cardLayout);

        var rightShell = new Panel
        {
            Dock = DockStyle.Fill,
            Padding = new Padding(40, 56, 40, 56),
            BackColor = UiTheme.Cream
        };
        rightShell.Controls.Add(card);

        var layout = new TableLayoutPanel
        {
            Dock = DockStyle.Fill,
            ColumnCount = 2
        };
        layout.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 56));
        layout.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 44));
        layout.Controls.Add(hero, 0, 0);
        layout.Controls.Add(rightShell, 1, 0);

        AcceptButton = loginButton;
        Controls.Add(layout);
    }

    private void HandleLogin()
    {
        try
        {
            if (string.IsNullOrWhiteSpace(_emailTextBox.Text))
            {
                MessageBox.Show("Please enter an email address.", "Missing email", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            var user = _authRepository.Authenticate(_emailTextBox.Text, _passwordTextBox.Text);
            if (user is null)
            {
                MessageBox.Show("User not found or password does not match.", "Authentication failed", MessageBoxButtons.OK, MessageBoxIcon.Error);
                return;
            }

            SessionContext.SetUser(user);
            Hide();

            using var mainForm = new MainForm();
            mainForm.ShowDialog();

            SessionContext.Clear();
            _passwordTextBox.Clear();
            Show();
        }
        catch (Exception ex)
        {
            MessageBox.Show($"Login error: {ex.Message}", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
        }
    }
}
