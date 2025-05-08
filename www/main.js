$(document).ready(function() {
    $('#test').click(function() {
        alert('Button clicked!');
    });

    // Validate add note form
    $('#note-form').submit(function(e) {
        let content = $('textarea[name="note_content"]').val().trim();
        if (!content) {
            e.preventDefault();
            alert('Note cannot be empty!');
        }
    });

    // Validate CSRF token for all forms
    $('form').submit(function(e) {
        let csrfToken = $('input[name="csrf_token"]').val();
        if (!csrfToken) {
            e.preventDefault();
            alert('CSRF token missing!');
        }
    });

    // Show edit form when clicking Edit button
    $('.edit-note-btn').click(function() {
        let noteId = $(this).data('id');
        let editForm = $('#edit-form-' + noteId);
        $('.edit-note-form').hide(); // Hide other edit forms
        editForm.show(); // Show the clicked note's edit form
    });

    // Hide edit form when clicking Cancel
    $('.cancel-edit').click(function() {
        let noteId = $(this).data('id');
        $('#edit-form-' + noteId).hide();
    });
});