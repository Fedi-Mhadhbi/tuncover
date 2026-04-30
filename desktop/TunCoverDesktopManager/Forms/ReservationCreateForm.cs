using TunCoverDesktopManager.Models;
using TunCoverDesktopManager.Repositories;
using TunCoverDesktopManager.Utils;

namespace TunCoverDesktopManager.Forms;

public sealed class ReservationCreateForm : Form
{
    private readonly ReservationRepository _reservationRepository = new();
    private readonly ActivityRepository _activityRepository = new();
    private readonly UserRepository _userRepository = new();
    private readonly ComboBox _activityComboBox = new() { Width = 280, DropDownStyle = ComboBoxStyle.DropDownList };
    private readonly ComboBox _touristComboBox = new() { Width = 280, DropDownStyle = ComboBoxStyle.DropDownList };
    private readonly DateTimePicker _datePicker = new() { Width = 180 };
    private readonly TextBox _timeTextBox = new() { Width = 160, Text = "10:00" };
    private readonly NumericUpDown _participantsInput = new() { Width = 120, Minimum = 1, Maximum = 20, Value = 1 };
    private readonly ComboBox _statusComboBox = new() { Width = 160, DropDownStyle = ComboBoxStyle.DropDownList };

    public ReservationCreateForm()
    {
        Text = "Create Reservation";
        StartPosition = FormStartPosition.CenterParent;
        MinimumSize = new Size(560, 520);
        BackColor = UiTheme.Sand;

        _statusComboBox.Items.AddRange(["en_attente", "confirmee", "terminee", "annulee"]);
        _statusComboBox.SelectedIndex = 0;
        UiTheme.StyleComboBox(_activityComboBox);
        UiTheme.StyleComboBox(_touristComboBox);
        UiTheme.StyleTextInput(_timeTextBox);
        UiTheme.StyleNumericUpDown(_participantsInput);
        UiTheme.StyleComboBox(_statusComboBox);

        Controls.Add(BuildLayout());
        LoadDropdowns();
    }

    private Control BuildLayout()
    {
        var card = UiTheme.CreateCard();
        card.Dock = DockStyle.Fill;

        var titleLabel = UiTheme.CreateSectionTitle("Create a new reservation");

        var saveButton = UiTheme.CreatePrimaryButton("Save");
        saveButton.Click += (_, _) => SaveReservation();

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
        layout.ColumnStyles.Add(new ColumnStyle(SizeType.Absolute, 140));
        layout.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 100));

        AddRow(layout, "Activity", _activityComboBox, 0);
        AddRow(layout, "Tourist", _touristComboBox, 1);
        AddRow(layout, "Activity date", _datePicker, 2);
        AddRow(layout, "Activity time", _timeTextBox, 3);
        AddRow(layout, "Participants", _participantsInput, 4);
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

    private void LoadDropdowns()
    {
        _activityComboBox.DataSource = _activityRepository.GetAllForDropdown();
        _activityComboBox.DisplayMember = "Value";
        _activityComboBox.ValueMember = "Key";

        _touristComboBox.DataSource = _userRepository.GetAllByRoleForDropdown("tourist");
        _touristComboBox.DisplayMember = "Value";
        _touristComboBox.ValueMember = "Key";
    }

    private void SaveReservation()
    {
        try
        {
            if (_activityComboBox.SelectedItem is not KeyValuePair<int, string> activityItem ||
                _touristComboBox.SelectedItem is not KeyValuePair<int, string> touristItem)
            {
                MessageBox.Show("Select an activity and a tourist.", "Missing data", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            var activity = _activityRepository.GetById(activityItem.Key);
            if (activity is null)
            {
                MessageBox.Show("Activity not found.", "Missing activity", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            var participants = Decimal.ToInt32(_participantsInput.Value);
            var reservation = new Reservation
            {
                ActivityId = activityItem.Key,
                TouristId = touristItem.Key,
                ActivityDate = _datePicker.Value.Date,
                ActivityTime = _timeTextBox.Text.Trim(),
                Participants = participants,
                Status = _statusComboBox.Text,
                TotalAmount = activity.Price * participants
            };

            _reservationRepository.Create(reservation);
            DialogResult = DialogResult.OK;
            Close();
        }
        catch (Exception ex)
        {
            MessageBox.Show($"Could not create reservation: {ex.Message}", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
        }
    }
}
