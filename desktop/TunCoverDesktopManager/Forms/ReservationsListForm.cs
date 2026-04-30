using System.Data;
using TunCoverDesktopManager.Repositories;
using TunCoverDesktopManager.Utils;

namespace TunCoverDesktopManager.Forms;

public sealed class ReservationsListForm : Form
{
    private readonly ReservationRepository _repository = new();
    private readonly TextBox _searchTextBox = new() { Width = 240, PlaceholderText = "Search reservations..." };
    private readonly ComboBox _reservationsComboBox = new() { Width = 300, DropDownStyle = ComboBoxStyle.DropDownList };
    private readonly DataGridView _grid = new() { Dock = DockStyle.Fill, ReadOnly = true, AllowUserToAddRows = false };

    public ReservationsListForm()
    {
        Text = "Reservations Management";
        StartPosition = FormStartPosition.CenterParent;
        MinimumSize = new Size(1120, 660);
        BackColor = UiTheme.Sand;

        UiTheme.StyleTextInput(_searchTextBox);
        UiTheme.StyleComboBox(_reservationsComboBox);
        UiTheme.StyleGrid(_grid);
        _grid.CellDoubleClick += (_, e) =>
        {
            if (e.RowIndex >= 0)
            {
                OpenDetails(GetSelectedGridId(e.RowIndex));
            }
        };

        var header = BuildHeader();
        var body = UiTheme.CreateCard();
        body.Dock = DockStyle.Fill;
        body.Controls.Add(_grid);

        Controls.Add(body);
        Controls.Add(header);

        LoadDropdown();
        LoadGrid();
    }

    private Control BuildHeader()
    {
        var container = new Panel
        {
            Dock = DockStyle.Top,
            Height = 110,
            Padding = new Padding(20),
            BackColor = UiTheme.Cream
        };

        var title = new Label
        {
            AutoSize = true,
            Text = "Reservations",
            Font = new Font("Georgia", 20, FontStyle.Regular),
            ForeColor = UiTheme.Ink,
            Location = new Point(0, 0)
        };

        var searchButton = UiTheme.CreatePrimaryButton("Search");
        searchButton.Click += (_, _) => LoadGrid();

        var detailsButton = UiTheme.CreateSecondaryButton("View Details");
        detailsButton.Click += (_, _) => OpenSelectedFromDropdown();

        var createButton = UiTheme.CreatePrimaryButton("Create Reservation");
        createButton.Click += (_, _) =>
        {
            using var form = new ReservationCreateForm();
            if (form.ShowDialog(this) == DialogResult.OK)
            {
                RefreshData();
            }
        };

        var actions = new FlowLayoutPanel
        {
            Location = new Point(0, 42),
            AutoSize = true,
            WrapContents = false
        };
        actions.Controls.Add(_searchTextBox);
        actions.Controls.Add(_reservationsComboBox);
        actions.Controls.Add(searchButton);
        actions.Controls.Add(detailsButton);
        actions.Controls.Add(createButton);

        container.Controls.Add(title);
        container.Controls.Add(actions);
        return container;
    }

    private void LoadGrid()
    {
        try
        {
            _grid.DataSource = _repository.Search(_searchTextBox.Text);
        }
        catch (Exception ex)
        {
            MessageBox.Show($"Could not load reservations: {ex.Message}", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
        }
    }

    private void LoadDropdown()
    {
        _reservationsComboBox.DataSource = _repository.GetAllForDropdown();
        _reservationsComboBox.DisplayMember = "Value";
        _reservationsComboBox.ValueMember = "Key";
    }

    private void RefreshData()
    {
        LoadDropdown();
        LoadGrid();
    }

    private void OpenSelectedFromDropdown()
    {
        if (_reservationsComboBox.SelectedItem is KeyValuePair<int, string> item)
        {
            OpenDetails(item.Key);
        }
    }

    private void OpenDetails(int id)
    {
        using var form = new ReservationDetailsForm(id);
        if (form.ShowDialog(this) == DialogResult.OK)
        {
            RefreshData();
        }
    }

    private int GetSelectedGridId(int rowIndex)
    {
        return Convert.ToInt32(((DataTable)_grid.DataSource!).Rows[rowIndex]["Id"]);
    }
}
