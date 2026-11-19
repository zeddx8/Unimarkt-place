# UniMarket Ghana — Student Entrepreneurship & Campus Commerce Platform

[![Platform](https://img.shields.io/badge/Platform-Ghana%20Campus%20Network-16A34A.svg)](#)
[![Currency](https://img.shields.io/badge/Currency-Ghana%20Cedis%20(GH₵)-success.svg)](#)
[![Deployment](https://img.shields.io/badge/Deployment-Windows%20.EXE%20%26%20.BAT-1E293B.svg)](#)
[![Status](https://img.shields.io/badge/Production-Campus--Grade-brightgreen.svg)](#)

**UniMarket Ghana** is a standalone, production-ready campus e-commerce and student entrepreneurship marketplace tailored for Ghanaian universities and colleges. Built with an authoritative design system (Deep Slate, Vibrant Orange, and Forest Green), campus-to-doorstep delivery, and seamless Ghanaian Mobile Money integration (MTN MoMo, Telecel Cash, AT Money, and Bank Cards).

---

## Key Commercial Capabilities & Localization

- **Ghana Cedis (`GH₵`) Currency Engine:** Centralized currency formatting (`formatPrice()`, `formatGHS()`) ensuring every price, subtotal, delivery fee, and discount is rendered cleanly with 2 decimal places (e.g. `GH₵ 450.00`, `GH₵ 1,250.00`).
- **Nationwide 16-Region Delivery:** Built for nationwide coverage across Greater Accra, Ashanti (Kumasi), Western (Takoradi), Central (Cape Coast), Eastern, Volta, Northern, and all other Ghanaian regions.
- **Local Payment Methods:** Integrated checkout supporting **MTN MoMo**, **Telecel Cash** (Vodafone Cash), **AT Money**, Visa/Mastercard, and Cash on Delivery (COD).
- **Ghanaian Phone Support:** Auto-formatted phone fields with Ghana prefix (`+233 XX XXX XXXX`) and one-click WhatsApp merchant inquiry links.
- **Standalone Windows 1-Click Deployment:** Ready-to-run Windows executable (`UniMarket-Deploy.exe`) and batch deployment (`deploy.bat`).

---

## Design System & Color Tokens

Defined in `css/style.css` following WCAG AA accessibility standards:

| Role | Color Name | Hex Code | Purpose |
|------|------------|----------|---------|
| **Primary Brand** | Deep Slate | `#1E293B` | Headers, primary typography, brand identity |
| **Primary Accent / CTA** | Vibrant Orange | `#EA580C` (hover `#C2410C`) | "Add to Cart", checkout buttons, primary actions |
| **Secondary Accent** | Forest Green | `#16A34A` | Stock status, paid badges, delivery confirmations |
| **Promotional Accent** | Warm Amber | `#D97706` | Star ratings, badges, review highlights |
| **Surfaces** | Pure White | `#FFFFFF` | Product cards, modals, checkout panels |
| **Background** | Warm Off-White | `#FAFAF9` | Platform background |
| **Borders** | Light Stone | `#E7E5E4` | Structural dividers, card borders |
| **Typography** | Sans-Serif | `Plus Jakarta Sans` | Modern, crisp commercial readability |

---

## Project Directory Structure

```
unimarket/
├── UniMarket-Deploy.exe   <- Standalone 1-click Windows deployment executable
├── deploy.bat             <- Automated Windows deployment script
├── build-exe.bat          <- C# / .NET compiler script for UniMarket-Deploy.exe
├── UniMarketLauncher.cs   <- Clean Windows launcher entry point source
├── reset-db.php           <- Clean database reset and seed script
├── package.json           <- Project metadata and build scripts
│
├── index.php              <- Dual-panel commercial sign-in portal
├── register.php           <- Customer & merchant account registration
├── dashboard.php          <- Nationwide marketplace catalog & live search
├── product.php            <- High-converting product page with star ratings
├── cart.php               <- Shopping cart & promotional coupon engine
├── checkout.php           <- Multi-step Ghanaian Mobile Money checkout
├── post.php               <- List a product for sale (GH₵ preview & photo upload)
├── chat.php               <- Real-time customer & merchant inquiry messaging
├── chat-fetch.php         <- AJAX background message sync
├── my-listings.php        <- Merchant inventory & order tracking dashboard
├── wishlist.php           <- Saved items with instant "Add to Cart"
├── wishlist-toggle.php    <- AJAX wishlist toggle endpoint
├── profile.php            <- Account settings & nationwide delivery profile
├── mark-sold.php          <- Mark product as sold endpoint
├── delete-product.php     <- Remove listing endpoint
├── logout.php             <- Secure session termination
│
├── admin/                 <- Merchant & Operations Admin Console
│   ├── index.php          <- Platform analytics, KPI cards, and order management
│   ├── login.php          <- Administrator login portal
│   ├── orders.php         <- Full order fulfillment & status updating
│   ├── products.php       <- Full product CRUD moderation & inventory controls
│   ├── categories.php     <- Full categories CRUD with vector icons & imagery
│   ├── users.php          <- Customer & merchant management
│   ├── reports.php        <- Customer report resolution & trust safety
│   ├── messages.php       <- Inquiry oversight & audit log
│   └── chat.php           <- Administrative customer mediation chat
│
├── includes/
│   ├── db.php             <- Central database connection, GH₵ formatters & helpers
│   ├── header.php         <- Global header, search bar, cart & wishlist counter
│   └── footer.php         <- Commercial multi-column footer & slide-out Cart Drawer
│
├── css/
│   └── style.css          <- Complete commercial stylesheet & responsive design system
├── js/
│   └── main.js            <- Client cart engine, GH₵ formatter, live filters & toasts
├── sql/
│   └── unimarket.sql      <- Complete database schema & realistic Ghanaian seeds
└── uploads/               <- Uploaded product photos and profile avatars
```

---

## 1-Click Windows Deployment

### Method 1: Standalone Windows Executable (`UniMarket-Deploy.exe`)
Simply **double-click** `UniMarket-Deploy.exe` in the root folder.
The launcher will automatically verify runtimes, prepare folders, start the local server, and open `http://127.0.0.1:8000/index.php` in your default browser.

### Method 2: Batch Script (`deploy.bat`)
Run the deployment script from PowerShell or Command Prompt:
```cmd
deploy.bat
```
The script:
1. Detects the **PHP runtime** across `PATH` and standard installation paths (`xampp`, `laragon`, etc.).
2. Checks **Node.js/npm** and runs dependency audit and build verification.
3. Automatically starts the PHP server on port `8000` (or `8080` if busy).
4. Automatically opens the live marketplace in your default browser.

### Rebuilding the Executable (`build-exe.bat`)
To recompile the executable launcher anytime:
```cmd
build-exe.bat
```
Uses the built-in Windows .NET Framework compiler (`csc.exe`) without needing external tools.

---

## Demo Login Credentials

Pre-configured accounts for testing:

| Account Type | Email Address | Password | Permissions |
|--------------|---------------|----------|-------------|
| **Student / Customer** | `kwame.mensah@unimarket.com.gh` | `password` | Shopping, cart, wishlist, orders |
| **Student Merchant / Founder** | `abena.osei@unimarket.com.gh` | `password` | Venture inventory, listings, orders, chat |
| **Platform Administrator** | `admin@unimarket.com.gh` | `password` | Full admin console, orders, CRUD |

*(Note: Production login form is completely clean of pre-filled demo boxes).*

---

## Database Reset & Seed Script

To wipe all test data and re-seed the super-admin account, categories, commercial products, and orders:
```cmd
php reset-db.php
```

Or import `sql/unimarket.sql` directly:
```cmd
mysql -u root unimarket < sql\unimarket.sql
```

---

## License & Commercial Rights

© 2026 **UniMarket Ghana**. All rights reserved.  
Universal commercial marketplace platform built for Ghana.
