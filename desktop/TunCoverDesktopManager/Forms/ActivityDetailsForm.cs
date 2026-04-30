using TunCoverDesktopManager.Repositories;
using TunCoverDesktopManager.Utils;

namespace TunCoverDesktopManager.Forms;

public sealed class ActivityDetailsForm : Form
{
    private readonly ActivityRepository _repository = new();
    private readonly int _activityId;
    private readonly Label _detailsLabel = new() { AutoSize = true, Font = new Font("Segoe UI", 10.5f), ForeColor = UiTheme.Ink };

    public ActivityDetailsForm(int activityId)
    {
        _activityId = activityId;
        Text = "Activity Details";
        StartPosition = FormStartPosition.CenterParent;
        MinimumSize = new Size(820, 540);
        BackColor = UiTheme.Sand;

        var editButton = UiTheme.CreatePrimaryButton("Edit");
        editButton.Click += (_, _) => EditActivity();

        var publishButton = UiTheme.CreateSecondaryButton("Publish");
        publishButton.Click += (_, _) => SetStatus("publie");

        var pauseButton = UiTheme.CreateSecondaryButton("Pause");
        pauseButton.Click += (_, _) => SetStatus("pause");

        var hideButton = UiTheme.CreateSecondaryButton("Archive");
        hideButton.Click += (_, _) => SetStatus("supprime");

        var deleteButton = UiTheme.CreateSecondaryButton("Delete");
        deleteButton.Click += (_, _) => DeleteActivity();

        var buttons = new FlowLayoutPanel { Dock = DockStyle.Bottom, Height = 56, WrapContents = true };
        buttons.Controls.Add(editButton);
        buttons.Controls.Add(publishButton);
        buttons.Controls.Add(pauseButton);
        buttons.Controls.Add(hideButton);
        buttons.Controls.Add(deleteButton);

        var card = UiTheme.CreateCard();
        card.Dock = DockStyle.Fill;

        var title = UiTheme.CreateSectionTitle("Activity Consultation");
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
        var activity = _repository.GetById(_activityId);
        if (activity is null)
        {
            _detailsLabel.Text = "Activity not found.";
            return;
        }

        _detailsLabel.Text =
            $"ID: {activity.Id}\n\n" +
            $"Title: {activity.Title}\n" +
            $"City: {activity.City}\n" +
            $"Price: {activity.Price:0.00} TND\n" +
            $"Duration: {activity.DurationHours} hour(s)\n" +
            $"Capacity: {activity.Capacity}\n" +
            $"Reserved: {activity.ReservedPlaces}\n" +
            $"Remaining: {activity.RemainingPlaces}\n" +
            $"Publication status: {activity.PublishStatus}\n" +
            $"Availability: {activity.AvailabilityStatus}\n\n" +
            $"Description:\n{activity.Description}";
    }

    private void EditActivity()
    {
        using var form = new ActivityEditForm(_activityId);
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
            _repository.SetStatus(_activityId, status);
            LoadData();
            DialogResult = DialogResult.OK;
        }
        catch (Exception ex)
        {
            MessageBox.Show($"Could not update activity: {ex.Message}", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
        }
    }

    private void DeleteActivity()
    {
        var confirm = MessageBox.Show("Delete this activity?", "Confirm delete", MessageBoxButtons.YesNo, MessageBoxIcon.Warning);
        if (confirm != DialogResult.Yes)
        {
            return;
        }

        try
        {
            _repository.Delete(_activityId);
            DialogResult = DialogResult.OK;
            Close();
        }
        catch (Exception ex)
        {
            MessageBox.Show($"Could not delete activity: {ex.Message}", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
        }
    }
}
