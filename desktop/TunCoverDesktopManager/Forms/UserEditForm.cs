using TunCoverDesktopManager.Models;
using TunCoverDesktopManager.Repositories;
using TunCoverDesktopManager.Utils;

namespace TunCoverDesktopManager.Forms;

public sealed class UserEditForm : Form
{
    private readonly UserRepository _repository = new();
    private readonly int _userId;
    private readonly TextBox _prenomTextBox = new() { Width = 260 };
    private readonly TextBox _nomTextBox = new() { Width = 260 };
    private readonly TextBox _emailTextBox = new() { Width = 260 };
    private readonly TextBox _telephoneTextBox = new() { Width = 260 };
    private readonly ComboBox _roleComboBox = new() { Width = 260, DropDownStyle = ComboBoxStyle.DropDownList };
    private readonly ComboBox _statusComboBox = new() { Width = 260, DropDownStyle = ComboBoxStyle.DropDownList };

    public UserEditForm(int userId)
    {
        _userId = userId;
        Text = "Edit User";
        StartPosition = FormStartPosition.CenterParent;
        MinimumSize = new Size(460, 450);
        BackColor = UiTheme.Sand;

        _roleComboBox.Items.AddRange(["admin", "artisan", "tourist"]);
        _statusComboBox.Items.AddRange(["actif", "inactif", "suspendu"]);
        UiTheme.StyleTextInput(_prenomTextBox);
        UiTheme.StyleTextInput(_nomTextBox);
        UiTheme.StyleTextInput(_emailTextBox);
        UiTheme.StyleTextInput(_telephoneTextBox);
        UiTheme.StyleComboBox(_roleComboBox);
        UiTheme.StyleComboBox(_statusComboBox);

        Controls.Add(BuildLayout());
        LoadUser();
    }

    private Control BuildLayout()
    {
        var card = UiTheme.CreateCard();
        card.Dock = DockStyle.Fill;

        var titleLabel = UiTheme.CreateSectionTitle("Modify user data");

        var saveButton = UiTheme.CreatePrimaryButton("Update");
        saveButton.Click += (_, _) => SaveUser();

        var cancelButton = UiTheme.CreateSecondaryButton("Cancel");
        cancelButton.Click += (_, _) => Close();

        var buttons = new FlowLayoutPanel { AutoSize = true, WrapContents = false };
        buttons.Controls.Add(saveButton);
        buttons.Controls.Add(cancelButton);

        var layout = new TableLayoutPanel
        {
            Dock = DockStyle.Fill,
            ColumnCount = 2,
            Padding = new Padding(8),
            AutoScroll = true
        };
        layout.ColumnStyles.Add(new ColumnStyle(SizeType.Absolute, 130));
        layout.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 100));

        AddRow(layout, "First name", _prenomTextBox, 0);
        AddRow(layout, "Last name", _nomTextBox, 1);
        AddRow(layout, "Email", _emailTextBox, 2);
        AddRow(layout, "Phone", _telephoneTextBox, 3);
        AddRow(layout, "Role", _roleComboBox, 4);
        AddRow(layout, "Status", _statusComboBox, 5);

        var wrapper = new FlowLayoutPanel
        {
            Dock = DockStyle.Fill,
            FlowDirection = FlowDirection.TopDown,
            WrapContents = false,
            AutoScroll = true
        };
        wrapper.Controls.Add(titleLabel);
        wrapper.Controls.Add(layout);
        wrapper.Controls.Add(buttons);

        card.Controls.Add(wrapper);
        return card;
    }

    private static void AddRow(TableLayoutPanel layout, string labelText, Control control, int row)
    {
        layout.RowCount = row + 1;
        layout.RowStyles.Add(new RowStyle(SizeType.Absolute, 44));
        layout.Controls.Add(new Label
        {
            Text = labelText,
            TextAlign = ContentAlignment.MiddleLeft,
            Dock = DockStyle.Fill,
            ForeColor = UiTheme.Ink
        }, 0, row);
        layout.Controls.Add(control, 1, row);
    }

    private void LoadUser()
    {
        var user = _repository.GetById(_userId);
        if (user is null)
        {
            MessageBox.Show("User not found.", "Missing user", MessageBoxButtons.OK, MessageBoxIcon.Warning);
            Close();
            return;
        }

        _prenomTextBox.Text = user.Prenom;
        _nomTextBox.Text = user.Nom;
        _emailTextBox.Text = user.Email;
        _telephoneTextBox.Text = user.Telephone;
        _roleComboBox.SelectedItem = user.Role;
        _statusComboBox.SelectedItem = user.Statut;
    }

    private void SaveUser()
    {
        try
        {
            var user = new User
            {
                Id = _userId,
                Prenom = _prenomTextBox.Text.Trim(),
                Nom = _nomTextBox.Text.Trim(),
                Email = _emailTextBox.Text.Trim(),
                Telephone = _telephoneTextBox.Text.Trim(),
                Role = _roleComboBox.Text,
                Statut = _statusComboBox.Text
            };

            _repository.Update(user);
            DialogResult = DialogResult.OK;
            Close();
        }
        catch (Exception ex)
        {
            MessageBox.Show($"Could not update user: {ex.Message}", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
        }
    }
}
