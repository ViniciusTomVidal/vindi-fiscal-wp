<?php
if (isset($_POST['submit'])) {
    // Save the token value to the wp_options table
    $token = sanitize_text_field($_POST['token']);
    update_option('_vindi_token', $token);
    echo '<div class="notice notice-success"><p>Token atualizado com sucesso!</p></div>';
}

// Retrieve the token value from the wp_options table
$token = get_option('_vindi_token');

// Display the form
?>
<div class="wrap">
    <h1>Configurações</h1>
    <form method="post" action="">

        <table class="form-table" role="presentation">
            <tbody>
            <tr class="form-field form-required">
                <th scope="row"><label for="user_login">Token <span
                                class="description">(obrigatório)</span></label></th>
                <td>
                    <input type="text" id="token" name="token" value="<?php echo esc_attr($token); ?>" required/>
                </td>
            </tr>
            </tbody>
        </table>
        <input type="submit" name="submit" value="Salvar" class="button button-primary"/>
    </form>
</div>