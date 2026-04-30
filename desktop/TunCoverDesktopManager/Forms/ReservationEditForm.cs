using TunCoverDesktopManager.Models;
using TunCoverDesktopManager.Repositories;
using TunCoverDesktopManager.Utils;

namespace TunCoverDesktopManager.Forms;

public sealed class ReservationEditForm : Form
{
    private readonly ReservationRepository _repository = new();
    private readonly int _reservationId;
    private readonly DateTimePicker _datePicker = new() { Width = 180 };
    private readonly TextBox _timeTextBox = new() { Width = 160 };
    private readonly NumericUpDown _participantsInput = new() { Width = 120, Minimum = 1, Maximum = 20 };
    private readonly ComboBox _statusComboBox = new() { Width = 180, DropDownStyle = ComboBoxStyle.DropDownList };

    public ReservationEditForm(int reservationId)
    {
        _reservationId = reservationId;
        Text = "Edit Reservation";
        StartPosition = FormStartPosition.CenterParent;
        MinimumSize = new Size(520, 420);
        BackColor = UiTheme.Sand;

        _statusComboBox.Items.AddRange(["en_attente", "confirmee", "terminee", "annulee"]);
        UiTheme.StyleTextInput(_timeTextBox);
        UiTheme.StyleNumericUpDown(_participantsInput);
        UiTheme.StyleComboBox(_statusComboBox);

        Controls.Add(BuildLayout());
        LoadReservation();
    }

    private Control BuildLayout()
    {
        var card = UiTheme.CreateCard();
        card.Dock = DockStyle.Fill;

        var titleLabel = UiTheme.CreateSectionTitle("Modify reservation data");

        var saveButton = UiTheme.CreatePrimaryButton("Update");
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

        AddRow(layout, "Activity date", _datePicker, 0);
        AddRow(layout, "Activity time", _timeTextBox, 1);
        AddRow(layout, "Participants", _participantsInput, 2);
        AddRow(layout, "Status", _statusComboBox, 3);

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

    private void LoadReservation()
    {
        var reservation = _repository.GetById(_reservationId);
        if (reservation is null)
        {
            MessageBox.Show("Reservation not found.", "Missing reservation", MessageBoxButtons.OK, MessageBoxIcon.Warning);
            Close();
            return;
        }

        _datePicker.Value = reservation.ActivityDate;
        _timeTextBox.Text = reservation.ActivityTime;
        _participantsInput.Value = reservation.Participants;
        _statusComboBox.SelectedItem = reservation.Status;
    }

    private void SaveReservation()
    {
        try
        {
            var existing = _repository.GetById(_reservationId);
            if (existing is null)
            {
                MessageBox.Show("Reservation not found.", "Missing reservation", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            var reservation = new Reservation
            {
                Id = _reservationId,
                ActivityDate = _datePicker.Value.Date,
                ActivityTime = _timeTextBox.Text.Trim(),
                Participants = Decimal.ToInt32(_participantsInput.Value),
                Status = _statusComboBox.Text
            };

            _repository.Update(reservation);
            DialogResult = DialogResult.OK;
            Close();
        }
        catch (Exception ex)
        {
            MessageBox.Show($"Could not update reservation: {ex.Message}", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
        }
    }
}
