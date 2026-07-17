# MedTurno

Gestão de escalas de plantão médico **multi-hospital**. Substitui o Excel + WhatsApp
por um app onde o gestor monta a escala, os médicos confirmam/trocam plantões e o
faturamento do mês é calculado automaticamente.

- **Stack:** Laravel 13 · Livewire 3 + Volt · Tailwind 3 · MySQL (prod) / SQLite (dev)
- **Regra de negócio:** concentrada em `app/Services` (não nos componentes)
- **Contrato do produto:** pasta [`specs/`](specs/) (leia o [README das specs](specs/README.md))

---

## Requisitos

- PHP **8.3+** com extensões: `pdo_sqlite`/`pdo_mysql`, `mbstring`, `openssl`, `curl`, `fileinfo`, `zip`, `intl`, `bcmath`
- Composer 2
- Node.js 20+ e npm

---

## Setup local

```bash
# 1. Dependências
composer install
npm install

# 2. Ambiente
cp .env.example .env          # Windows PowerShell: Copy-Item .env.example .env
php artisan key:generate

# 3. Banco (dev usa SQLite em arquivo)
php artisan migrate

# 4. Front-end (gera o manifest do Vite — obrigatório mesmo pra rodar os testes)
npm run build

# 5. Subir tudo junto (server + queue + logs + vite)
composer dev
# ou só o servidor:
php artisan serve
```

Abra <http://localhost:8000>. Em dev os e-mails caem em `storage/logs/laravel.log`
(driver `log`), então dá pra copiar o link de convite de lá sem provedor real.

---

## Testes e qualidade

```bash
php artisan test           # Pest — suíte completa
vendor/bin/phpstan analyse # Larastan (análise estática)
vendor/bin/pint            # Laravel Pint (code style; use --test pra só checar)
```

> Os testes rodam em SQLite `:memory:` (ver `phpunit.xml`). **Rode `npm run build`
> antes** — sem o manifest do Vite as views quebram com _"Vite manifest not found"_.

---

## Deploy (produção)

Alvo de referência: **VPS Linux (Ubuntu 22.04+)** com nginx + PHP-FPM 8.3 + MySQL 8.
Serve igual em Railway/Forge (que automatizam os passos de servidor).

### 1. Provisionar o servidor

```bash
sudo apt update
sudo apt install -y nginx mysql-server php8.3-fpm php8.3-mysql php8.3-mbstring \
  php8.3-xml php8.3-curl php8.3-zip php8.3-intl php8.3-bcmath unzip git
# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
# Node (pro build de assets)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

### 2. Banco de dados

```sql
CREATE DATABASE medturno CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'medturno'@'localhost' IDENTIFIED BY 'senha-forte-aqui';
GRANT ALL PRIVILEGES ON medturno.* TO 'medturno'@'localhost';
FLUSH PRIVILEGES;
```

### 3. Código + dependências

```bash
git clone <repo> /var/www/medturno
cd /var/www/medturno
composer install --no-dev --optimize-autoloader
npm ci && npm run build
cp .env.example .env
php artisan key:generate
```

### 4. Configurar o `.env` de produção

Edite `/var/www/medturno/.env` (guiado pelos comentários do `.env.example`):

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://medturno.com.br      # domínio real — usado nos links de convite

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=medturno
DB_USERNAME=medturno
DB_PASSWORD=senha-forte-aqui

SESSION_SECURE_COOKIE=true           # cookie só via HTTPS

# E-mail (Resend ou Brevo via SMTP — ver comentários no .env.example)
MAIL_MAILER=smtp
MAIL_HOST=smtp.resend.com
MAIL_PORT=587
MAIL_USERNAME=resend
MAIL_PASSWORD=re_xxxxxxxx
MAIL_SCHEME=tls
MAIL_FROM_ADDRESS="nao-responda@medturno.com.br"   # domínio verificado no provedor
```

### 5. Migrar + otimizar

```bash
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo chown -R www-data:www-data storage bootstrap/cache
```

### 6. Fila e agendador (obrigatórios)

Os e-mails/notificações vão pra fila e o fechamento de plantões roda por schedule.

**Worker da fila** — `/etc/systemd/system/medturno-worker.service`:

```ini
[Unit]
Description=MedTurno queue worker
After=network.target

[Service]
User=www-data
Restart=always
ExecStart=/usr/bin/php /var/www/medturno/artisan queue:work --tries=3 --timeout=90

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl enable --now medturno-worker
```

**Agendador** — cron do `www-data` (`sudo crontab -u www-data -e`):

```cron
* * * * * cd /var/www/medturno && php artisan schedule:run >> /dev/null 2>&1
```

Isso dispara o comando `plantoes:fechar` (confirmados → concluídos, pendentes
vencidos → não cumpridos) diariamente às 03:00.

### 7. nginx + HTTPS

`/etc/nginx/sites-available/medturno`:

```nginx
server {
    listen 80;
    server_name medturno.com.br;
    root /var/www/medturno/public;

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* { deny all; }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/medturno /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx

# HTTPS grátis com Let's Encrypt
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d medturno.com.br
```

### 8. Backup diário do MySQL

Cron do root:

```cron
0 3 * * * mysqldump -u medturno -p'senha-forte-aqui' medturno | gzip > /var/backups/medturno-$(date +\%F).sql.gz && find /var/backups -name 'medturno-*.sql.gz' -mtime +30 -delete
```

### 9. Deploy de atualizações

```bash
cd /var/www/medturno
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
sudo systemctl restart medturno-worker
```

---

## Segurança já embutida

- Cabeçalhos de segurança (CSP, HSTS, X-Frame-Options…) via `App\Http\Middleware\SecurityHeaders` (CSP/HSTS ativam só em `production`)
- Rate limit: login (5 tentativas), verificação de e-mail (6/min), aceite de convite (20/min)
- Tokens de convite hasheados no banco (SHA-256); o link só existe em memória no momento do convite
- Health check em `/up` (pra uptime monitor / load balancer)

## Fora do escopo do MVP

Pagamento/NF, integração automática de WhatsApp, PDF/Excel, admin SaaS e afins —
ver [`specs/09-fora-do-escopo.md`](specs/09-fora-do-escopo.md).
