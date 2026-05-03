# Asset platform - ระบบงานครุภัณฑ์

โปรเจ็กต์นี้เป็นระบบงานองค์กรที่ทำงานแบบ frontend SPA + PHP API โดยปัจจุบันมีทั้งระบบทะเบียนครุภัณฑ์ และระบบแจ้งซ่อม

- frontend shell ใช้ `index.html`
- frontend bootstrap อยู่ที่ `js/main.js`
- backend API หลักเข้าได้ผ่าน `api.php`
- งานส่งออกและงานพิมพ์เข้าได้ผ่าน `export.php`
- LINE Messaging API callback เข้าได้ผ่าน `line/webhook.php`
- business logic ของระบบครุภัณฑ์อยู่ใน `modules/inventory/`
- business logic ของระบบแจ้งซ่อมอยู่ใน `modules/repair/`
- ความสามารถส่วนกลางของแพลตฟอร์มอยู่ใน `modules/index/`

## Stack และ runtime model

### Frontend

- Now.js เป็น framework ฝั่ง client สำหรับ route, component, template binding, auth integration และ UI managers
- SPA shell โหลด bundle จาก `Now/dist/`
- template ของหน้าใช้งานจริงถูก render ตาม route จากโฟลเดอร์ `templates/`, `templates/inventory/` และ `templates/repair/`
- `modules/inventory/admin.js` ลงทะเบียน route ฝั่งทะเบียนครุภัณฑ์ เช่น `/inventory-assets`, `/inventory-categories`, `/inventory-settings`
- `modules/repair/admin.js` ลงทะเบียน route ฝั่งแจ้งซ่อม เช่น `/repair-request`, `/repair-history`, `/repair-jobs`, `/repair-settings`, `/repair-statuses`

### Backend

- Kotchasan เป็น framework ฝั่ง PHP ที่รับผิดชอบ routing, request/response, model/query, config และ utilities
- GCMS layer ให้ base controller สำหรับ admin/API/table workflow
- controller ฝั่ง PHP จัดตาม module namespace เช่น `Index\\Config\\Controller`

## Entry points ที่สำคัญ

| Entry point | หน้าที่ |
| --- | --- |
| `index.html` | SPA shell หลักของระบบ |
| `js/main.js` | initialize Now.js, auth, csrf, i18n, router และ route ฝั่ง settings/platform |
| `api.php` | จุดเข้า API หลักของแอป |
| `export.php` | จุดเข้างาน export/print |
| `line/webhook.php` | รับ LINE webhook |
| `load.php` | bootstrap ค่าระบบ, debug flags, DB logging และ include Kotchasan |

## โครงสร้างโมดูลหลัก

### `modules/inventory/`

โมดูลนี้เป็นฐานข้อมูลครุภัณฑ์และทรัพย์สินที่ใช้อ้างอิงกับงานแจ้งซ่อม

- `controllers/assets.php` และ `controllers/asset.php` ใช้จัดการทะเบียนครุภัณฑ์และ modal form สำหรับเพิ่มหรือแก้ไขรายการ
- `controllers/categories.php` และ `controllers/category.php` ใช้จัดการ master data ของหมวดหมู่ ประเภท รุ่น และหน่วยนับ
- `controllers/settings.php` ใช้กำหนดค่ารูปภาพและการแจ้งเตือนประกัน
- `models/assets.php` และ `models/asset.php` รวม query หลักของ asset header, inventory items และ metadata

### `modules/repair/`

โมดูลนี้ดูแลงานแจ้งซ่อมที่ผูกกับ `inventory_items.product_no`

- `controllers/repair.php` ใช้โหลดและบันทึกคำขอแจ้งซ่อม
- `controllers/history.php` ใช้แสดงประวัติการแจ้งซ่อมของผู้แจ้ง
- `controllers/jobs.php` ใช้แสดงงานซ่อมสำหรับเจ้าหน้าที่และผู้ดูแล
- `controllers/status.php` ใช้บันทึกสถานะซ่อมครั้งละหนึ่งรายการลง `repair_status`
- `controllers/settings.php` และ `controllers/statuses.php` ใช้กำหนด running number และ workflow status

### `modules/index/`

โมดูลนี้เป็น shared platform layer ของ repository

- auth และ session restore
- profile และ user management
- permissions และ user status
- settings pages หลายหมวด เช่น general, company, email, api, theme, line, telegram, sms
- public config endpoint สำหรับ login page และ frontend theme/config
- social login callback/config

### `modules/download/`

- ใช้แสดงรายการไฟล์แนบ ดาวน์โหลดไฟล์ และช่วยดึง attachment metadata

### `modules/export/`

- มี shared export controller สำหรับ HTML print page และ CSV export
- `export.php` จะชี้งานไปยัง controller ฝั่ง export ของระบบ

## Frontend routing model

`js/main.js` เป็นจุดรวม route ฝั่งแพลตฟอร์ม เช่น login, profile, users, settings และ system pages ส่วน route ของ business modules ถูกเพิ่มผ่าน `modules/inventory/admin.js` และ `modules/repair/admin.js`

route ที่สำคัญ เช่น

- `/inventory-assets` ใช้หน้าทะเบียนครุภัณฑ์
- `/inventory-categories` ใช้หน้าจัดการหมวดหมู่ inventory
- `/inventory-settings` ใช้หน้าตั้งค่าของโมดูลครุภัณฑ์
- `/repair-request` ใช้หน้าสร้างหรือแก้ไขคำขอแจ้งซ่อม
- `/repair-history` ใช้หน้าติดตามประวัติแจ้งซ่อมของผู้ใช้
- `/repair-jobs` ใช้หน้ารายการงานซ่อมสำหรับเจ้าหน้าที่
- `/repair-settings` ใช้หน้าตั้งค่าของโมดูลซ่อม
- `/repair-statuses` ใช้หน้าจัดการสถานะงานซ่อม

## Business rules ที่ควรรู้

### Approval workflow

- workflow อิงกับ status ของสมาชิกและ department
- admin สามารถเข้าถึง approval area ได้
- final approval จะตรวจ availability ซ้ำอีกครั้ง
- final approval ต้องมี self-drive หรือ assigned driver ที่ใช้ได้จริง

### Cancellation policy

- requester cancellation policy ถูกกำหนดจาก config
- รองรับหลายระดับ เช่น pending-only, before date, before start, before end, always
- officer cancellation แยก flow ออกจาก requester cancellation

## Data model ที่สำคัญ

| Table | บทบาท |
| --- | --- |
| `inventory` | header ของครุภัณฑ์หรือทรัพย์สินแต่ละรุ่น |
| `inventory_items` | serial/registration number, stock และ unit ของ asset จริง |
| `inventory_meta` | metadata ของ asset เช่น ผู้ถือครอง, สถานที่, วันหมดประกัน |
| `repair` | header ของคำขอแจ้งซ่อม |
| `repair_status` | ประวัติสถานะการซ่อม, ผู้รับผิดชอบ, ค่าใช้จ่าย |
| `number` | running number ต่อ prefix ที่ใช้สร้างเลขเอกสาร เช่น job no |
| `category` | master data กลาง เช่น department,  |
| `user` | บัญชีผู้ใช้, social identity, permissions, status |
| `user_meta` | metadata ของผู้ใช้ เช่น department |
| `logs` | audit/history ภายในระบบ |

แนวทาง data modeling ของ repo นี้ใช้ table หลักร่วมกับ meta table เพื่อให้ขยายข้อมูลเพิ่มได้โดยไม่ต้องแก้ schema หลักบ่อย

## Notification และ external integration

### ที่ใช้จริงในระบบจองรถ

- Email
- LINE
- Telegram

### ความสามารถเพิ่มเติมในระดับแพลตฟอร์ม

- social login: Google, Facebook, LINE, Telegram
- LINE callback และ LINE webhook
- Telegram settings
- SMS settings

## การติดตั้ง

ระบบมี installer ในโฟลเดอร์ `install/` ซึ่งรองรับทั้งการติดตั้งใหม่และการอัปเกรดเวอร์ชัน

### สิ่งที่ installer ตรวจ

- PHP 7.4 ขึ้นไป
- PDO MySQL
- mbstring
- zlib
- JSON
- XML
- OpenSSL
- GD
- cURL

### สิ่งที่ installer ทำ

1. รับค่าการเชื่อมต่อฐานข้อมูล
2. สร้างฐานข้อมูลเมื่อยังไม่มี
3. import schema จาก `install/database.sql`
4. seed ข้อมูลเริ่มต้นของระบบ
5. สร้าง `settings/database.php` และ `settings/config.php`
6. สร้าง admin account เริ่มต้น

## Logging, debug และ validation notes

- `load.php` กำหนด `DEBUG`, `DB_LOG`, `DB_LOG_FILE`, `DB_LOG_RETENTION_DAYS`
- เมื่อเปิด SQL log ข้อมูล query จะถูกส่งไปที่ `datas/logs/sql_log.php`
