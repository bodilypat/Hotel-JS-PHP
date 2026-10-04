hotel-management-system/
│
├── frontend/
│   ├── index.html
│   │
│   ├── pages/
│   │   ├── auth/
│   │   │   ├── login.html
│   │   │   ├── register.html
│   │   │   └── forgot-password.html
│   │   │
│   │   ├── rooms/
│   │   │   ├── rooms.html
│   │   │   └── room-details.html
│   │   │
│   │   ├── booking/
│   │   │   ├── booking.html
│   │   │   ├── booking-details.html
│   │   │   └── booking-success.html
│   │   │
│   │   ├── guests/
│   │   │   ├── guests.html
│   │   │   └── guest-details.html
│   │   │
│   │   ├── payments/
│   │   │   ├── payments.html
│   │   │   └── invoice.html
│   │   │
│   │   ├── staff/
│   │   │   └── staff.html
│   │   │
│   │   └── reports/
│   │       ├── reports.html
│   │       ├── revenue-report.html
│   │       └── occupancy-report.html
│   │
│   ├── dashboard/
│   │   ├── dashboard.html
│   │   └── profile.html
│   │
│   ├── components/
│   │   ├── navbar.html
│   │   ├── sidebar.html
│   │   ├── footer.html
│   │   ├── room-card.html
│   │   ├── booking-card.html
│   │   ├── alert.html
│   │   └── modal.html
│   │
│   ├── css/
│   │   ├── style.css
│   │   ├── variables.css
│   │   ├── navbar.css
│   │   ├── sidebar.css
│   │   ├── dashboard.css
│   │   ├── rooms.css
│   │   ├── bookings.css
│   │   ├── guests.css
│   │   ├── payments.css
│   │   ├── forms.css
│   │   ├── tables.css
│   │   └── responsive.css
│   │
│   ├── js/
│   │   ├── main.js
│   │   ├── api.js
│   │   ├── auth.js
│   │   ├── dashboard.js
│   │   ├── rooms.js
│   │   ├── guests.js
│   │   ├── bookings.js
│   │   ├── payments.js
│   │   ├── staff.js
│   │   ├── reports.js
│   │   └── validation.js
│   │
│   └── assets/
│       ├── images/
│       │   ├── hotel/
│       │   ├── rooms/
│       │   ├── services/
│       │   └── users/
│       └── icons/
│
├── backend/
│   ├── public/
│   │   └── index.php
│   │
│   ├── config/
│   │   ├── database.php
│   │   ├── app.php
│   │   └── constants.php
│   │
│   ├── controllers/
│   │   ├── AuthController.php
│   │   ├── UserController.php
│   │   ├── RoomController.php
│   │   ├── GuestController.php
│   │   ├── BookingController.php
│   │   ├── PaymentController.php
│   │   ├── StaffController.php
│   │   └── ReportController.php
│   │
│   ├── models/
│   │   ├── User.php
│   │   ├── Room.php
│   │   ├── Guest.php
│   │   ├── Booking.php
│   │   ├── Payment.php
│   │   └── Staff.php
│   │
│   ├── services/
│   │   ├── AuthService.php
│   │   ├── RoomService.php
│   │   ├── BookingService.php
│   │   ├── PaymentService.php
│   │   └── ReportService.php
│   │
│   ├── routes/
│   │   ├── auth.php
│   │   ├── users.php
│   │   ├── rooms.php
│   │   ├── guests.php
│   │   ├── bookings.php
│   │   ├── payments.php
│   │   ├── staff.php
│   │   └── reports.php
│   │
│   ├── middleware/
│   │   ├── auth.php
│   │   ├── role.php
│   │   └── cors.php
│   │
│   ├── validation/
│   │   ├── authValidation.php
│   │   ├── roomValidation.php
│   │   ├── guestValidation.php
│   │   └── bookingValidation.php
│   │
│   ├── helpers/
│   │   ├── response.php
│   │   ├── security.php
│   │   ├── jwt.php
│   │   └── logger.php
│   │
│   └── storage/
│       └── logs/
│
├── database/
│   ├── migrations/
│   │   ├── users.sql
│   │   ├── rooms.sql
│   │   ├── guests.sql
│   │   ├── bookings.sql
│   │   ├── payments.sql
│   │   └── staff.sql
│   │
│   ├── hotel_management.sql
│   └── seed_data.sql
│
├── .gitignore
└── README.md




┌─────────────────────────────────────────┐
│               FRONTEND                  │
│                                         │
│ HTML + CSS + JavaScript                 │
│                                         │
│ Login │ Dashboard │ Rooms │ Bookings    │
│ Guests │ Payments │ Staff │ Reports     │
└───────────────────┬─────────────────────┘
                    │
                    │ HTTP / REST API
                    ▼
┌─────────────────────────────────────────┐
│                BACKEND                  │
│                  PHP                    │
│                                         │
│ Routes → Middleware → Controller        │
│                    ↓                    │
│                 Service                 │
│                    ↓                    │
│                  Model                  │
└───────────────────┬─────────────────────┘
                    │
                    │ PDO / SQL
                    ▼
┌─────────────────────────────────────────┐
│                 MySQL                   │
│                                         │
│ Users │ Rooms │ Guests │ Bookings       │
│ Payments │ Staff                        │
└─────────────────────────────────────────┘

main.js          → common application functionality
api.js           → API communication
auth.js          → login/logout/session
dashboard.js     → dashboard data
rooms.js         → room operations
guests.js        → guest operations
bookings.js      → reservation operations
payments.js      → payment/billing operations
staff.js         → staff operations
reports.js       → report generation
validation.js    → form validation
