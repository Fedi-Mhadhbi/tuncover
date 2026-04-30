using TunCoverDesktopManager.Models;
using TunCoverDesktopManager.Repositories;
using TunCoverDesktopManager.Utils;

namespace TunCoverDesktopManager.Forms;

public sealed class ActivityEditForm : Form
{
    private readonly ActivityRepository _repository = new();
    private readonly int _activityId;
    private readonly NumericUpDown _artisanIdInput = new() { Width = 120, Minimum = 1, Maximum = 100000 };
    private readonly TextBox _titleTextBox = new() { Width = 280 };
    private readonly TextBox _cityTextBox = new() { Width = 280 };
    private readonly TextBox _descriptionTextBox = new() { Width = 280, Multiline = true, Height = 96 };
    private readonly NumericUpDown _priceInput = new() { Width = 120, Minimum = 0, Maximum = 100000, DecimalPlaces = 2 };
    private readonly NumericUpDown _durationInput = new() { Width = 120, Minimum = 1, Maximum = 72 };
    private readonly NumericUpDown _capacityInput = new() { Width = 120, Minimum = 1, Maximum = 500 };
    private readonly ComboBox _statusComboBox = new() { Width = 160, DropDownStyle = ComboBoxStyle.DropDownList };

    public ActivityEditForm(int activityId)
    {
        _activityId = activityId;
        Text = "Edit Activity";
        StartPosition = FormStartPosition.CenterParent;
        MinimumSize = new Size(540, 600);
        BackColor = UiTheme.Sand;

        _statusComboBox.Items.AddRange(["publie", "brouillon", "pause", "supprime"]);
        UiTheme.StyleNumericUpDown(_artisanIdInput);
        UiTheme.StyleTextInput(_titleTextBox);
        UiTheme.StyleTextInput(_cityTextBox);
        UiTheme.StyleTextInput(_descriptionTextBox);
        UiTheme.StyleNumericUpDown(_priceInput);
        UiTheme.StyleNumericUpDown(_durationInput);
        UiTheme.StyleNumericUpDown(_capacityInput);
        UiTheme.StyleComboBox(_statusComboBox);

        Controls.Add(BuildLayout());
        LoadActivity();
    }

    private Control BuildLayout()
    {
        var card = UiTheme.CreateCard();
        card.Dock = DockStyle.Fill;

        var titleLabel = UiTheme.CreateSectionTitle("Modify activity data");

        var saveButton = UiTheme.CreatePrimaryButton("Update");
        saveButton.Click += (_, _) => SaveActivity();

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

        AddRow(layout, "Artisan ID", _artisanIdInput, 0);
        AddRow(layout, "Title", _titleTextBox, 1);
        AddRow(layout, "City", _cityTextBox, 2);
        AddRow(layout, "Description", _descriptionTextBox, 3);
        AddRow(layout, "Price (TND)", _priceInput, 4);
        AddRow(layout, "Duration (h)", _durationInput, 5);
        AddRow(layout, "Capacity", _capacityInput, 6);
        AddRow(layout, "Publish status", _statusComboBox, 7);

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
        layout.RowStyles.Add(new RowStyle(SizeType.Absolute, control is TextBox { Multiline: true } ? 108 : 44));
        layout.Controls.Add(new Label
        {
            Text = labelText,
            TextAlign = ContentAlignment.MiddleLeft,
            Dock = DockStyle.Fill,
            ForeColor = UiTheme.Ink
        }, 0, row);
        layout.Controls.Add(control, 1, row);
    }

    private void LoadActivity()
    {
        var activity = _repository.GetById(_activityId);
        if (activity is null)
        {
            MessageBox.Show("Activity not found.", "Missing activity", MessageBoxButtons.OK, MessageBoxIcon.Warning);
            Close();
            return;
        }

        _artisanIdInput.Value = activity.ArtisanId;
        _titleTextBox.Text = activity.Title;
        _cityTextBox.Text = activity.City;
        _descriptionTextBox.Text = activity.Description;
        _priceInput.Value = activity.Price;
        _durationInput.Value = activity.DurationHours;
        _capacityInput.Value = activity.Capacity;
        _statusComboBox.SelectedItem = activity.PublishStatus;
    }

    private void SaveActivity()
    {
        try
        {
            var activity = new Activity
            {
                Id = _activityId,
                ArtisanId = Decimal.ToInt32(_artisanIdInput.Value),
                Title = _titleTextBox.Text.Trim(),
                City = _cityTextBox.Text.Trim(),
                Description = _descriptionTextBox.Text.Trim(),
                Price = _priceInput.Value,
                DurationHours = Decimal.ToInt32(_durationInput.Value),
                Capacity = Decimal.ToInt32(_capacityInput.Value),
                PublishStatus = _statusComboBox.Text
            };

            _repository.Update(activity);
            DialogResult = DialogResult.OK;
            Close();
        }
        catch (Exception ex)
        {
            MessageBox.Show($"Could not update activity: {ex.Message}", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
        }
    }
}
