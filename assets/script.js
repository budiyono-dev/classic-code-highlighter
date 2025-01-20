jQuery(document).ready(function($) {
    $('#cch-add-field').click(function() {
        nextFieldKey++;
        var newField = `
            <div class="cch-field">
                <select name="cch_fields[${nextFieldKey}][language]">
                    <option value="language-plaintext">plaintext</option>
                    <option value="language-json">JSON</option>
                    <option value="language-php">PHP</option>
                    <option value="language-javascript">Javascript</option>
                    <option value="language-java">Java</option>
                    <option value="language-xml">XML</option>
                    <option value="language-sql">SQL</option>
                    <option value="language-css">CSS</option>
                </select>
                <input type="text" name="cch_fields[${nextFieldKey}][filename]" placeholder="Filename">
                <textarea name="cch_fields[${nextFieldKey}][source_code]" placeholder="Source Code"></textarea>
                <button type="button" class="button cch-remove-field">Remove</button>
            </div>
        `;
        $('#cch-fields-container').append(newField);
    });

    $(document).on('click', '.cch-remove-field', function() {
        $(this).closest('.dcf-field').remove();
    });
});
