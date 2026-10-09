# Inventory

ระบบบริหารจัดการสินค้า/พัสดุคงคลัง พร้อมระบบแจ้งซ่อม สร้างบน [Now.js](https://nowjs.net) และ Kotchasan (PHP)

*Web-based inventory management with a built-in repair module, built on Now.js and Kotchasan (PHP).*

## ✨ คุณสมบัติ / Features

- **สินค้าและพัสดุ** – ทะเบียนสินค้า (Product Master), รายชิ้น/ครุภัณฑ์, หมวดหมู่และประเภทที่กำหนดเองได้
- **สต๊อก** – ยอดคงเหลือ, การเคลื่อนไหวสต๊อก (รับ/จ่าย/ปรับยอด), ชั้นต้นทุน (cost layer) และการตัดต้นทุน
- **นำเข้า/ส่งออก CSV** – นำเข้าสินค้าจำนวนมาก (ดูตัวอย่าง `product.csv`) และส่งออกรายงาน
- **งานซ่อม (Repair)** – รับแจ้งซ่อม, ติดตามสถานะ, ประวัติการซ่อม, เลขใบงานอัตโนมัติ (เช่น `JOB202601-0001`)
- **จัดการผู้ใช้และสิทธิ์** – สมาชิก ผู้ดูแลระบบ ช่างซ่อม ผู้รับผิดชอบ พร้อมสิทธิ์ละเอียดรายโมดูล
- **แจ้งเตือน** – LINE, Telegram, SMS และอีเมล
- **AI Assistant** – รองรับ Claude, Gemini, DeepSeek และ OpenAI-compatible API
- **REST API** – ยืนยันตัวตนด้วย Bearer token / JWT, จำกัด IP และ CORS ได้
- **รองรับ PWA** – ติดตั้งบนมือถือได้ (`manifest.json`, `service-worker.js`)
- **หลายภาษา** – ไทย / อังกฤษ (`language/`)

## 📋 ความต้องการของระบบ / Requirements

- PHP 7.4 ขึ้นไป (PDO MySQL)
- MySQL 5.7+ / MariaDB 10.3+ (ต้องมีสิทธิ์ `CREATE`, `ALTER`, `DROP`)
- Apache พร้อม `mod_rewrite` และอนุญาต `.htaccess` (`AllowOverride All`)
- Node.js เฉพาะกรณีต้อง build ไฟล์ frontend ใหม่ (ไม่จำเป็นสำหรับการใช้งานทั่วไป)

## 🚀 การติดตั้ง / Installation

1. ดาวน์โหลดหรือ clone โปรเจ็ค แล้วนำไปวางบนเว็บเซิร์ฟเวอร์

   ```bash
   git clone https://github.com/goragodwiriya/inventory.git
   ```

2. สร้างฐานข้อมูลเปล่า (แนะนำ `utf8mb4`)
3. ให้สิทธิ์เขียนกับโฟลเดอร์ `settings/` และ `datas/`

   ```bash
   chmod -R 775 settings datas
   ```

4. เปิดเบราว์เซอร์ไปที่ `http://your-domain/install/` แล้วทำตามขั้นตอน (ตรวจระบบ → ตั้งค่าฐานข้อมูล → สร้างผู้ดูแลระบบ)
5. เมื่อติดตั้งเสร็จ เข้าสู่ระบบด้วยบัญชีผู้ดูแลที่สร้างไว้ในขั้นตอนที่ 4

> 🔒 **หลังติดตั้งเสร็จ** ควรลบหรือจำกัดการเข้าถึงโฟลเดอร์ `install/` บนเซิร์ฟเวอร์จริง

### ติดตั้งผ่านบรรทัดคำสั่ง

สคริปต์ใน `install/` (`cli-fresh.php`, `cli-upgrade.php`, `cli-verify.php` ฯลฯ) ใช้ติดตั้งใหม่ ปรับรุ่น และตรวจสอบความถูกต้องของฐานข้อมูลได้โดยไม่ต้องผ่านเบราว์เซอร์

### การปรับรุ่น / Upgrading

วางไฟล์เวอร์ชันใหม่ทับ (โฟลเดอร์ `settings/` และ `datas/` จะไม่ถูกแตะ) แล้วเปิด `/install/` อีกครั้ง ตัวปรับรุ่นจะตรวจเงื่อนไขก่อนแตะฐานข้อมูล และหยุดพร้อมแจ้งสาเหตุหากเงื่อนไขไม่ครบ **สำรองฐานข้อมูลก่อนปรับรุ่นเสมอ**

## 🗂️ โครงสร้างโปรเจ็ค / Structure

```
Gcms/          คลาสแกนของ GCMS (Api, Controller, Line, Telegram, Ai, ...)
Kotchasan/     เฟรมเวิร์ก PHP
Now/           เฟรมเวิร์ก JavaScript ฝั่ง client
modules/
  index/       หน้าหลัก ผู้ใช้ ตั้งค่าระบบ สิทธิ์ ภาษา
  inventory/   สินค้า สต๊อก หมวดหมู่ นำเข้า/ส่งออก
  repair/      งานซ่อม
  download/    ไฟล์ดาวน์โหลด
  export/      ส่งออกข้อมูล
  timeline/    Timeline provider
install/       ตัวติดตั้งและตัวปรับรุ่น
language/      ไฟล์ภาษา (th, en)
line/ telegram/ Webhook สำหรับ LINE และ Telegram
settings/      ไฟล์ตั้งค่า (สร้างตอนติดตั้ง ไม่ถูกเก็บใน git)
```

## ⚙️ การตั้งค่า / Configuration

ค่าตั้งต่าง ๆ แก้ได้จากเมนู **ตั้งค่า** ในระบบ โดยจะถูกบันทึกที่ `settings/config.php` และ `settings/database.php`

> ⚠️ ไฟล์ใน `settings/` มีรหัสผ่านฐานข้อมูล, API token และ JWT secret **ห้าม commit ขึ้น GitHub** (ถูกระบุไว้ใน `.gitignore` แล้ว)

### REST API

เรียก API พร้อมส่ง token ในส่วนหัว

```
Authorization: Bearer <token>
```

ดู token และกำหนด IP ที่อนุญาตได้ในหน้าตั้งค่า API ของระบบ

### Webhook

| บริการ   | Endpoint                |
|----------|-------------------------|
| LINE     | `/line/webhook.php`     |
| Telegram | `/telegram/webhook.php` |

## 🛠️ การพัฒนา / Development

```bash
npm install          # ติดตั้ง dependencies (เฉพาะงานพัฒนา frontend)
npm run dev          # เปิด dev server
npm run build        # build bundles
npm test             # รัน unit test
```

## 📚 เอกสารอ้างอิง / Documentation

- [Now.js](https://nowjs.net) · [เอกสาร Now.js](https://docs.nowjs.net)
- [Kotchasan Framework](https://www.kotchasan.com)

## 🤝 การมีส่วนร่วม / Contributing

พบปัญหาหรือมีข้อเสนอแนะ แจ้งได้ที่ [Issues](https://github.com/goragodwiriya/inventory/issues) ส่วน Pull Request ยินดีรับ โปรดอธิบายสิ่งที่เปลี่ยนและวิธีทดสอบ

## 📄 สัญญาอนุญาต / License

[MIT License](LICENSE) © 2026 Goragod Wiriya

## 👤 ผู้พัฒนา / Author

Goragod Wiriya – <https://github.com/goragodwiriya>
