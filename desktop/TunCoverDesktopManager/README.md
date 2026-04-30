# TunCover Desktop Admin/Artisan Manager

WinForms desktop application built with C# and ADO.NET for managing:

- Users
- Activities
- Reservations

## Required tools

- .NET 8 SDK
- Visual Studio 2022 Community or later with the `Desktop development with .NET` workload
- Windows machine
- MySQL database running locally

## Current database

This project is configured for:

- Server: `localhost`
- Database: `tuncover`
- User: `root`
- Password: empty

You can change the connection string in:

- `Config/DbConfig.cs`

## Project architecture

- `Models`: entity classes
- `Data`: database connection factory
- `Repositories`: ADO.NET CRUD logic
- `Forms`: WinForms interfaces
- `Services`: session context
- `Utils`: shared UI theme helpers

## Required assignment mapping

- Login interface: `LoginForm`
- Main interface: `MainForm`
- Management interfaces: `UsersListForm`, `ActivitiesListForm`, `ReservationsListForm`
- Create interfaces: `UserCreateForm`, `ActivityCreateForm`, `ReservationCreateForm`
- Details interfaces: `UserDetailsForm`, `ActivityDetailsForm`, `ReservationDetailsForm`
- Edit interfaces: `UserEditForm`, `ActivityEditForm`, `ReservationEditForm`

## Notes

- The project was scaffolded manually because the local machine did not yet have the .NET SDK available at generation time.
- The solution file is:
  - `TunCoverDesktop.sln`
- Open the solution in Visual Studio or run from terminal inside the project folder.
- Build commands:

```powershell
dotnet restore
dotnet build
dotnet run
```
