using System.Drawing.Drawing2D;
using System.Net.Http;

namespace TunCoverDesktopManager.Utils;

public static class UiTheme
{
    public static readonly Color Ink = Color.FromArgb(26, 20, 16);
    public static readonly Color Clay = Color.FromArgb(201, 112, 58);
    public static readonly Color Sand = Color.FromArgb(245, 240, 232);
    public static readonly Color Cream = Color.FromArgb(253, 250, 245);
    public static readonly Color TextMuted = Color.FromArgb(107, 94, 82);
    public static readonly Color Success = Color.FromArgb(83, 136, 76);
    public static readonly Color Danger = Color.FromArgb(176, 72, 57);
    public static readonly Color Border = Color.FromArgb(225, 212, 197);
    public const string BrandImageUrl = "https://images.unsplash.com/photo-1539020140153-e479b8c22e70?w=1600&q=80";

    public static Button CreatePrimaryButton(string text)
    {
        var button = new Button
        {
            Text = text,
            AutoSize = false,
            Width = 150,
            Height = 42,
            BackColor = Clay,
            ForeColor = Color.White,
            FlatStyle = FlatStyle.Flat,
            Font = new Font("Segoe UI", 10, FontStyle.Bold),
            Cursor = Cursors.Hand
        };
        button.FlatAppearance.BorderSize = 0;
        MakeRounded(button, 20);
        return button;
    }

    public static Button CreateSecondaryButton(string text)
    {
        var button = new Button
        {
            Text = text,
            AutoSize = false,
            Width = 140,
            Height = 38,
            BackColor = Color.FromArgb(248, 242, 234),
            ForeColor = Ink,
            FlatStyle = FlatStyle.Flat,
            Font = new Font("Segoe UI", 9.5f, FontStyle.Bold),
            Cursor = Cursors.Hand
        };
        button.FlatAppearance.BorderColor = Border;
        button.FlatAppearance.BorderSize = 1;
        MakeRounded(button, 18);
        return button;
    }

    public static Panel CreateCard()
    {
        var panel = new Panel
        {
            BackColor = Color.White,
            Padding = new Padding(18),
            Margin = new Padding(12),
            BorderStyle = BorderStyle.FixedSingle
        };
        panel.Paint += (_, e) =>
        {
            using var pen = new Pen(Border, 1);
            e.Graphics.DrawRectangle(pen, 0, 0, panel.Width - 1, panel.Height - 1);
        };
        return panel;
    }

    public static void StyleGrid(DataGridView grid)
    {
        grid.BackgroundColor = Color.White;
        grid.BorderStyle = BorderStyle.None;
        grid.EnableHeadersVisualStyles = false;
        grid.ColumnHeadersDefaultCellStyle.BackColor = Ink;
        grid.ColumnHeadersDefaultCellStyle.ForeColor = Color.White;
        grid.ColumnHeadersDefaultCellStyle.Font = new Font("Segoe UI", 10, FontStyle.Bold);
        grid.ColumnHeadersHeight = 42;
        grid.ColumnHeadersBorderStyle = DataGridViewHeaderBorderStyle.None;
        grid.DefaultCellStyle.BackColor = Color.White;
        grid.DefaultCellStyle.ForeColor = Ink;
        grid.DefaultCellStyle.SelectionBackColor = Color.FromArgb(244, 225, 209);
        grid.DefaultCellStyle.SelectionForeColor = Ink;
        grid.DefaultCellStyle.Font = new Font("Segoe UI", 9.5f);
        grid.DefaultCellStyle.Padding = new Padding(6);
        grid.CellBorderStyle = DataGridViewCellBorderStyle.SingleHorizontal;
        grid.GridColor = Color.FromArgb(240, 232, 222);
        grid.RowHeadersVisible = false;
        grid.AutoSizeColumnsMode = DataGridViewAutoSizeColumnsMode.Fill;
        grid.SelectionMode = DataGridViewSelectionMode.FullRowSelect;
        grid.MultiSelect = false;
        grid.RowTemplate.Height = 40;
    }

    public static void StyleTextInput(TextBox textBox)
    {
        textBox.BackColor = Color.White;
        textBox.ForeColor = Ink;
        textBox.Font = new Font("Segoe UI", 10);
        textBox.BorderStyle = BorderStyle.FixedSingle;
        textBox.Margin = new Padding(0, 0, 0, 10);
    }

    public static void StyleComboBox(ComboBox comboBox)
    {
        comboBox.BackColor = Color.White;
        comboBox.ForeColor = Ink;
        comboBox.Font = new Font("Segoe UI", 10);
        comboBox.FlatStyle = FlatStyle.Flat;
        comboBox.Margin = new Padding(0, 0, 0, 10);
    }

    public static void StyleNumericUpDown(NumericUpDown input)
    {
        input.BackColor = Color.White;
        input.ForeColor = Ink;
        input.Font = new Font("Segoe UI", 10);
        input.BorderStyle = BorderStyle.FixedSingle;
        input.Margin = new Padding(0, 0, 0, 10);
    }

    public static Label CreateSectionTitle(string text)
    {
        return new Label
        {
            AutoSize = true,
            Text = text,
            Font = new Font("Georgia", 19, FontStyle.Regular),
            ForeColor = Ink
        };
    }

    public static Panel CreateHeroPanel(string title, string subtitle, string body)
    {
        var panel = new Panel
        {
            Dock = DockStyle.Fill,
            Margin = new Padding(0),
            Padding = new Padding(34),
            BackColor = Ink
        };

        panel.BackgroundImageLayout = ImageLayout.Stretch;
        TryLoadRemoteImage(panel, BrandImageUrl);

        panel.Paint += (_, e) =>
        {
            using var brush = new LinearGradientBrush(panel.ClientRectangle,
                Color.FromArgb(210, 26, 20, 16),
                Color.FromArgb(125, 26, 20, 16),
                LinearGradientMode.Vertical);
            e.Graphics.FillRectangle(brush, panel.ClientRectangle);
        };

        var tag = new Label
        {
            AutoSize = true,
            Text = "Cultural Tourism Platform",
            Font = new Font("Segoe UI", 9, FontStyle.Bold),
            ForeColor = Color.FromArgb(232, 196, 96),
            BackColor = Color.Transparent
        };

        var titleLabel = new Label
        {
            AutoSize = false,
            Width = 360,
            Height = 120,
            Text = title,
            Font = new Font("Georgia", 28, FontStyle.Regular),
            ForeColor = Color.White,
            BackColor = Color.Transparent
        };

        var subtitleLabel = new Label
        {
            AutoSize = true,
            Text = subtitle,
            Font = new Font("Segoe UI Semibold", 11, FontStyle.Regular),
            ForeColor = Color.FromArgb(245, 231, 216),
            BackColor = Color.Transparent
        };

        var bodyLabel = new Label
        {
            AutoSize = false,
            Width = 360,
            Height = 120,
            Text = body,
            Font = new Font("Segoe UI", 10),
            ForeColor = Color.FromArgb(234, 224, 215),
            BackColor = Color.Transparent
        };

        var stats = new Label
        {
            AutoSize = true,
            Text = "240+ Cultural Sites   •   180+ Artisans   •   24 Regions",
            Font = new Font("Segoe UI", 9, FontStyle.Bold),
            ForeColor = Color.FromArgb(232, 196, 96),
            BackColor = Color.Transparent
        };

        var layout = new FlowLayoutPanel
        {
            Dock = DockStyle.Fill,
            FlowDirection = FlowDirection.TopDown,
            WrapContents = false,
            BackColor = Color.Transparent
        };
        layout.Controls.Add(tag);
        layout.Controls.Add(new Label { AutoSize = true, Height = 10, BackColor = Color.Transparent });
        layout.Controls.Add(titleLabel);
        layout.Controls.Add(subtitleLabel);
        layout.Controls.Add(bodyLabel);
        layout.Controls.Add(stats);

        panel.Controls.Add(layout);
        return panel;
    }

    public static void MakeRounded(Control control, int radius)
    {
        void ApplyRegion()
        {
            var path = new GraphicsPath();
            path.StartFigure();
            path.AddArc(new Rectangle(0, 0, radius, radius), 180, 90);
            path.AddArc(new Rectangle(control.Width - radius, 0, radius, radius), 270, 90);
            path.AddArc(new Rectangle(control.Width - radius, control.Height - radius, radius, radius), 0, 90);
            path.AddArc(new Rectangle(0, control.Height - radius, radius, radius), 90, 90);
            path.CloseFigure();
            control.Region = new Region(path);
        }

        control.Resize += (_, _) => ApplyRegion();
        ApplyRegion();
    }

    private static void TryLoadRemoteImage(Control control, string imageUrl)
    {
        try
        {
            using var client = new HttpClient
            {
                Timeout = TimeSpan.FromSeconds(8)
            };
            var imageBytes = client.GetByteArrayAsync(imageUrl).GetAwaiter().GetResult();
            using var stream = new MemoryStream(imageBytes);
            control.BackgroundImage = Image.FromStream(stream);
        }
        catch
        {
            control.BackgroundImage = null;
        }
    }
}
