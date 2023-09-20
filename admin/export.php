<?php
if (isset($_POST['submit'])) {
    // Save the token value to the wp_options table
    $date_start = sanitize_text_field($_POST['date_start']);
    update_option('_date_start', $date_start);

    $date_end = sanitize_text_field($_POST['date_end']);
    update_option('_date_end', $date_end);

    $upload_dir = wp_upload_dir();

    $subdirectory = 'planilhas';

    echo '<div class="notice notice-success"><p>Exportação gerado com sucesso! <a target="_blank" href="'.get_site_url() . '/wp-content/uploads/planilhas/file-'. explode(" ", date_s($_POST['date_start']))[0].'-'.explode(" ", date_s($_POST['date_end']))[0].'.xlsx'.'">Clique aqui para fazer o download</a></p></div>';
}

function date_s($inputDateTime)
{
    $dateTime = date_create_from_format('Y-m-d\TH:i', $inputDateTime);
    $formattedDateTime = date_format($dateTime, 'Y-m-d H:i:s');

    return $formattedDateTime;
}


// Retrieve the token value from the wp_options table
$date_start = get_option('_date_start');
$date_end = get_option('_date_end');

// Display the form
?>
<div class="wrap">
    <h1>Exportar relatório fiscal</h1>
    <form method="post" action="">
        <table class="form-table" role="presentation">
            <tbody>
                <tr class="form-field form-required">
                    <th scope="row">
                        <label for="user_login">Data inicial <span
                                class="description">(obrigatório)</span>
                        </label>
                    </th>
                    <td>
                        <input type="datetime-local" id="date_start" name="date_start" value="<?php echo esc_attr($date_start); ?>" required/>
                    </td>
                </tr>
                <tr class="form-field form-required">
                    <th scope="row">
                        <label for="user_login">Data final <span
                                class="description">(obrigatório)</span>
                        </label>
                    </th>
                    <td>
                        <input type="datetime-local" id="date_end" name="date_end" value="<?php echo esc_attr($date_end); ?>" required/>
                    </td>
                </tr>
            </tbody>
        </table>
        <input type="submit" name="submit" value="Exportar" class="button button-primary" />
    </form>
</div>