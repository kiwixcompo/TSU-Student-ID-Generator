# TSU Student ID Generator

Student ID card registration and generation portal for Taraba State University (TSU). 

This progressive web application allows students to register, verify their details, and generates a fully-featured, front-and-back digital ID card with integrated QR Code verification and automated PNG export. 

## Features
- **Student Dashboard:** Easy-to-use form for students to enter their registration metadata (Faculty, Department, Blood Group, Passport Photo).
- **Admin Dashboard:** Overview of all registrations and management capabilities.
- **Smart ID Card Generation:** Dynamically creates the Front and Back of the TSU ID card, fully styled with standard university dimensions.
- **QR Code Verification:** Automatically embeds a QR code on the back of the card containing essential student details and a built-in verify URL.
- **High-Quality Exporting:** Exports the front and back of the generated ID card separately to a high-resolution PNG file (`html-to-image`).

## Tech Stack
- **Framework:** Next.js 15 (App Router)
- **Language:** TypeScript
- **Styling:** Tailwind CSS (v4)
- **Icons:** Lucide React
- **QR Generation:** `qrcode.react`
- **Image Export:** `html-to-image`
- **Animations:** Framer Motion (`motion`)

## Setup Instructions

### Prerequisites
Make sure you have [Node.js](https://nodejs.org/) installed on your machine.

### 1. Clone the repository
```bash
git clone https://github.com/your-username/tsu-student-id-generator.git
cd tsu-student-id-generator
```

### 2. Install dependencies
```bash
npm install
```

### 3. Run the development server
```bash
npm run dev
```

Open [http://localhost:3000](http://localhost:3000) with your browser to see the result.

## Usage
1. Navigate to the registration page (`/register`) to enroll.
2. A unique link will be generated for the administrator to review and print/download the ID card.
3. On the `admin/id-card` page, an exact replica of the physical ID is rendered on-screen. Click **Export as PNG** to automatically convert it to high-res images for bulk-printing.
4. Scan the QR code on the generated back cover to access the instant student validation link.

## License
MIT
