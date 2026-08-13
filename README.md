# Camp Management System

نظام Laravel لإدارة مخيمات النازحين: المخيمات، العائلات، الأفراد، المساعدات، التقارير، الخريطة، وصلاحيات المستخدمين. يدعم واجهة ويب وAPI لتطبيق Flutter عبر Laravel Sanctum.

## المتطلبات

- PHP 8.2+
- Composer
- Node.js & npm
- MySQL (XAMPP) أو SQLite أو PostgreSQL

## التثبيت

```bash
composer install
cp .env.example .env
php artisan key:generate
```

### إعداد قاعدة البيانات (XAMPP / MySQL)

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=camp_management
DB_USERNAME=root
DB_PASSWORD=
```

```bash
php artisan migrate --seed
npm install && npm run build
php artisan serve
```

## حسابات تجريبية (بعد seed)

| الدور | البريد | كلمة المرور |
|-------|--------|-------------|
| مدير النظام | admin@camp.org | admin123 |
| مشرف مخيم | ahmed.supervisor@camp.org | supervisor123 |
| مدير مخيم | sami.manager@camp.org | manager123 |

> **مهم:** غيّر كلمات المرور قبل النشر على الإنتاج.

## API (Flutter)

```http
POST /api/login
Content-Type: application/json
Accept: application/json

{"email":"...", "password":"..."}
```

الاستجابة تحتوي على `token` — أرسله في الطلبات التالية:

```http
Authorization: Bearer {token}
Accept: application/json
```

المسارات المحمية موثّقة في `routes/api.php`.

## الصلاحيات

- **admin:** وصول كامل لكل المخيمات
- **supervisor / camp_manager:** مقيدون بمخيمهم (`camp_id`)
- صلاحيات granular عبر middleware `permission:`

## الإنتاج

```env
APP_ENV=production
APP_DEBUG=false
```

## الاختبارات

```bash
php artisan test
```

## License

MIT
