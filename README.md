# Student Disciplinary System

This project is a Student Disciplinary System that allows for the management of student records and disciplinary incidents. It provides a user-friendly interface for performing CRUD (Create, Read, Update, Delete) operations on students and incidents.

## Features

- **Student Management**: Create, view, edit, and delete student records.
- **Incident Management**: Create, view, edit, and delete disciplinary incidents associated with students.
- **Responsive Design**: The application is designed to be responsive and user-friendly.

## Technologies Used

- **Backend**: PHP with SQL for database management.
- **Frontend**: HTML, CSS, and JavaScript for the user interface.
- **Database**: MySQL (or any compatible SQL database).

## Project Structure

```
student-discipline-system
├── src
│   ├── controllers
│   ├── models
│   ├── views
│   ├── core
│   └── helpers
├── public
│   ├── css
│   └── js
├── sql
├── config
├── routes
├── tests
├── composer.json
├── .env
├── .gitignore
└── README.md
```

## Installation

1. Clone the repository:
   ```
   git clone <repository-url>
   ```

2. Navigate to the project directory:
   ```
   cd student-discipline-system
   ```

3. Install dependencies using Composer:
   ```
   composer install
   ```

4. Set up the database:
   - Import the `schema.sql` file to create the necessary tables.
   - Optionally, run the `seed.sql` file to populate the database with initial data.

5. Configure your database connection in the `.env` file.

6. Start the server and access the application via your web browser.

## Usage

- Navigate to the students section to manage student records.
- Navigate to the incidents section to manage disciplinary incidents.
- Use the provided forms to create or edit records as needed.

## Contributing

Contributions are welcome! Please submit a pull request or open an issue for any enhancements or bug fixes.

## License

This project is licensed under the MIT License.