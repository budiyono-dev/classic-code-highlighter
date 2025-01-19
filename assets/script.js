jQuery(document).ready(function($) {
    $('#dcf-add-field').click(function() {
        var newField = `
            <div class="dcf-field">
                <input type="text" name="dcf_fields[${nextFieldKey}][title]" placeholder="Field Title">
                <textarea name="dcf_fields[${nextFieldKey}][content]" placeholder="Field Content"></textarea>
                <button type="button" class="button dcf-remove-field">Remove</button>
            </div>
        `;
        $('#dcf-fields-container').append(newField);
        nextFieldKey++;
    });

    $(document).on('click', '.dcf-remove-field', function() {
        $(this).closest('.dcf-field').remove();
    });
});
