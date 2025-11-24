document.addEventListener('DOMContentLoaded', function() {
    const studentForm = document.getElementById('studentForm');
    const incidentForm = document.getElementById('incidentForm');

    if (studentForm) {
        studentForm.addEventListener('submit', function(event) {
            event.preventDefault();
            const formData = new FormData(studentForm);
            fetch('/api/students', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Student created successfully!');
                    window.location.href = '/students';
                } else {
                    alert('Error creating student: ' + data.message);
                }
            });
        });
    }

    if (incidentForm) {
        incidentForm.addEventListener('submit', function(event) {
            event.preventDefault();
            const formData = new FormData(incidentForm);
            fetch('/api/incidents', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Incident created successfully!');
                    window.location.href = '/incidents';
                } else {
                    alert('Error creating incident: ' + data.message);
                }
            });
        });
    }

    const deleteButtons = document.querySelectorAll('.delete-button');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const studentId = this.dataset.id;
            if (confirm('Are you sure you want to delete this student?')) {
                fetch(`/api/students/${studentId}`, {
                    method: 'DELETE'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Student deleted successfully!');
                        window.location.reload();
                    } else {
                        alert('Error deleting student: ' + data.message);
                    }
                });
            }
        });
    });
});