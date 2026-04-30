using TunCoverDesktopManager.Repositories;
using TunCoverDesktopManager.Utils;

namespace TunCoverDesktopManager.Forms;

public sealed class ReservationDetailsForm : Form
{
    private readonly ReservationRepository _repository = new();
    private readonly int _reservationId;
    private readonly Label _detailsLabel = new() { AutoSize = true, Font = new Font("Segoe UI", 10.5f), ForeColor = UiTheme.Ink };

    public ReservationDetailsForm(int reservationId)
    {
        _reservationId = reservationId;
        Text = "Reservation Details";
        StartPosition = FormStartPosition.CenterParent;
        MinimumSize = new Size(920, 540);
        BackColor = UiTheme.Sand;

        var editButton = UiTheme.CreatePrimaryButton("Edit");
        editButton.Click += (_, _) => EditReservation();

        var confirmButton = UiTheme.CreateSecondaryButton("Confirm");
        confirmButton.Click += (_, _) => SetStatus("confirmee");

        var completeButton = UiTheme.CreateSecondaryButton("Complete");
        completeButton.Click += (_, _) => SetStatus("terminee");

        var cancelButton = UiTheme.CreateSecondaryButton("Cancel");
        cancelButton.Click += (_, _) => SetStatus("annulee");

        var refundButton = UiTheme.CreateSecondaryButton("Refund");
        refundButton.Click += (_, _) => RefundReservation();

        var buttons = new FlowLayoutPanel { Dock = DockStyle.Bottom, Height = 56, WrapContents = true };
        buttons.Controls.Add(editButton);
        buttons.Controls.Add(confirmButton);
        buttons.Controls.Add(completeButton);
        buttons.Controls.Add(cancelButton);
        buttons.Controls.Add(refundButton);

        var card = UiTheme.CreateCard();
        card.Dock = DockStyle.Fill;

        var title = UiTheme.CreateSectionTitle("Reservation Consultation");
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
        var reservation = _repository.GetById(_reservationId);
        if (reservation is null)
        {
            _detailsLabel.Text = "Reservation not found.";
            return;
        }

        _detailsLabel.Text =
            $"ID: {reservation.Id}\n\n" +
            $"Activity: {reservation.ActivityTitle}\n" +
            $"Tourist: {reservation.TouristName}\n" +
            $"Reservation date: {reservation.ReservationDate:yyyy-MM-dd HH:mm}\n" +
            $"Activity date: {reservation.ActivityDate:yyyy-MM-dd}\n" +
            $"Activity time: {reservation.ActivityTime}\n" +
            $"Participants: {reservation.Participants}\n" +
            $"Total amount: {reservation.TotalAmount:0.00} TND\n" +
            $"Reservation status: {reservation.Status}\n" +
            $"Payment status: {reservation.PaymentStatus}";
    }

    private void EditReservation()
    {
        using var form = new ReservationEditForm(_reservationId);
        if (form.ShowDialog(this) == DialogResult.OK)
        {
            LoadData();
            DialogResult = DialogResult.OK;
        }
    }

    private void SetStatus(string status)
    {
        try
        {
            _repository.SetStatus(_reservationId, status);
            LoadData();
            DialogResult = DialogResult.OK;
        }
        catch (Exception ex)
        {
            MessageBox.Show($"Could not update reservation: {ex.Message}", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
        }
    }

    private void RefundReservation()
    {
        try
        {
            _repository.Refund(_reservationId);
            LoadData();
            DialogResult = DialogResult.OK;
        }
        catch (Exception ex)
        {
            MessageBox.Show($"Could not refund reservation: {ex.Message}", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
        }
    }
}
