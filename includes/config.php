<?php
// includes/config.php

define('AUTH_URL', 'https://auth.mercadopago.com/authorization');
define('TOKEN_URL', 'https://api.mercadopago.com/oauth/token');

// Redirecionamento padrão configurável
define('REDIRECT_URI', admin_url('admin-post.php?action=mp_oauth_callback'));
