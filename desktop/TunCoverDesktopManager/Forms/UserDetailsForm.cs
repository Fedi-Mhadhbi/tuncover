using TunCoverDesktopManager.Repositories;
using TunCoverDesktopManager.Utils;

namespace TunCoverDesktopManager.Forms;

public sealed class UserDetailsForm : Form
{
    private readonly UserRepository _repository = new();
    private readonly int _userId;
    private readonly Label _detailsLabel = new() { AutoSize = true, Font = new Font("Segoe UI", 10.5f), ForeColor = UiTheme.Ink };

    public UserDetailsForm(int userId)
    {
        _userId = userId;
        Text = "User Details";
        StartPosition = FormStartPosition.CenterParent;
        MinimumSize = new Size(760, 500);
        BackColor = UiTheme.Sand;

        var editButton = UiTheme.CreatePrimaryButton("Edit");
        editButton.Click += (_, _) => EditUser();

        var ban7Button = UiTheme.CreateSecondaryButton("Ban 7 Days");
        ban7Button.Click += (_, _) => BanUser(7);

        var ban30Button = UiTheme.CreateSecondaryButton("Ban 30 Days");
        ban30Button.Click += (_, _) => BanUser(30);

        var activateButton = UiTheme.CreateSecondaryButton("Reactivate");
        activateButton.Click += (_, _) => ReactivateUser();

        var deleteButton = UiTheme.CreateSecondaryButton("Delete");
        deleteButton.Click += (_, _) => DeleteUser();

        var buttons = new FlowLayoutPanel { Dock = DockStyle.Bottom, Height = 56, WrapContents = true };
        buttons.Controls.Add(editButton);
        buttons.Controls.Add(ban7Button);
        buttons.Controls.Add(ban30Button);
        buttons.Controls.Add(activateButton);
        buttons.Controls.Add(deleteButton);

        var card = UiTheme.CreateCard();
        card.Dock = DockStyle.Fill;

        var title = UiTheme.CreateSectionTitle("User Consultation");
        title.Location = new Point(18, 18);
        _detailsLabel.Location = new Point(22, 72);

        card.Controls.Add(title);
        card.Controls.Add(_detailsLabel);
        card.Controls.Add(buttons);

        Controls.Add(card);
        LoadData();
    }

    private void LoadData()
    {
        var user = _repository.GetById(_userId);
        if (user is null)
        {
            _detailsLabel.Text = "User not found.";
            return;
        }

        _detailsLabel.Text =
            $"ID: {user.Id}\n\n" +
            $"Name: {user.FullName}\n" +
            $"Email: {user.Email}\n" +
            $"Role: {user.Role}\n" +
            $"Status: {user.Statut}\n" +
            $"Phone: {user.Telephone}\n" +
            $"Registered on: {user.DateInscription:yyyy-MM-dd HH:mm}\n" +
            $"Ban until: {(user.BanUntil.HasValue ? user.BanUntil.Value.ToString("yyyy-MM-dd HH:mm") : "-")}";
    }

    private void EditUser()
    {
        using var form = new UserEditForm(_userId);
        if (form.ShowDialog(this) == DialogResult.OK)
        {
            LoadData();
            DialogResult = DialogResult.OK;
        }
    }

    private void BanUser(int days)
    {
        try
        {
            _repository.BanUntil(_userId, DateTime.Now.AddDays(days));
            LoadData();
            DialogResult = DialogResult.OK;
        }
        catch (Exception ex)
        {
            MessageBox.Show($"Could not ban user: {ex.Message}", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
        }
    }

    private void ReactivateUser()
    {
        try
        {
            _repository.ClearBan(_userId);
            LoadData();
            DialogResult = DialogResult.OK;
        }
        catch (Exception ex)
        {
            MessageBox.Show($"Could not reactivate user: {ex.Message}", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
        }
    }

    private void DeleteUser()
    {
        var confirm = MessageBox.Show("Delete this user?", "Confirm delete", MessageBoxButtons.YesNo, MessageBoxIcon.Warning);
        if (confirm != DialogResult.Yes)
        {
            return;
        }

        try
        {
            _repository.Delete(_userId);
            DialogResult = DialogResult.OK;
            Close();
        }
        catch (Exception ex)
        {
            MessageBox.Show($"Could not delete user: {ex.Message}", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
        }
    }
}
