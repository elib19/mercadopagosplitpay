<?php
// includes/config.php

// URL para autenticação e troca de token
define('AUTH_URL', 'https://auth.mercadopago.com/authorization');
define('TOKEN_URL', 'https://api.mercadopago.com/oauth/token');

// URI de redirecionamento após a autenticação
define('REDIRECT_URI', admin_url('admin-post.php?action=mp_oauth_callback'));
?>
