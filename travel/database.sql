-- ============================================================
--  سياحة — نظام مكتب سياحة وحجز رحلات
--  ملف قاعدة البيانات: database.sql
--  قاعدة البيانات: travel_db (utf8mb4)
--
--  طريقة الاستخدام:
--   1) أنشئ قاعدة البيانات travel_db في phpMyAdmin
--   2) استورد هذا الملف
--   3) عدّل بيانات الاتصال في db.php إن لزم
--
--  الحسابات الافتراضية (غيّرها فور النشر!):
--   المدير:  admin    / admin123
--   وكيل 1:  sanaa_travel / agent123
--   وكيل 2:  aden_travel  / agent123
--   عميل 1:  ahmed      / client123
--   عميل 2:  fatima     / client123
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS packages;
DROP TABLE IF EXISTS destinations;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
-- جدول المستخدمين
-- ------------------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'agent', 'client') NOT NULL DEFAULT 'client',
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    bio TEXT,
    profile_image VARCHAR(255),
    years_of_experience INT DEFAULT 0,
    license_number VARCHAR(100),
    license_image VARCHAR(255),
    id_image VARCHAR(255),
    status ENUM('active', 'pending', 'suspended') DEFAULT 'pending',
    agree_to_terms BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- جدول الباقات السياحية
-- ------------------------------------------------------------
CREATE TABLE packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    destination VARCHAR(100) NOT NULL,
    duration INT NOT NULL,
    type ENUM('family', 'adventure', 'cultural', 'beach', 'religious', 'business') NOT NULL,
    price DECIMAL(12,2) NOT NULL,
    includes TEXT,
    excludes TEXT,
    itinerary TEXT,
    image VARCHAR(255),
    status ENUM('available', 'reserved', 'cancelled') DEFAULT 'available',
    agent_id INT,
    views INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (agent_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_packages_destination (destination),
    INDEX idx_packages_status (status),
    INDEX idx_packages_agent (agent_id)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- جدول الحجوزات
-- ------------------------------------------------------------
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    package_id INT NOT NULL,
    client_id INT NOT NULL,
    booking_date DATE NOT NULL,
    number_of_travelers INT NOT NULL DEFAULT 1,
    total_price DECIMAL(12,2) NOT NULL,
    status ENUM('pending', 'approved', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    special_requests TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_bookings_status (status),
    INDEX idx_bookings_client (client_id)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- جدول الوجهات السياحية
-- ------------------------------------------------------------
CREATE TABLE destinations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    country VARCHAR(100) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_destinations_name (name)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- جدول التقييمات
-- ------------------------------------------------------------
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    package_id INT NOT NULL,
    client_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_review (package_id, client_id),
    FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ============================================================
-- البيانات الأولية
-- ============================================================

-- ---------- المستخدمون ----------
-- (كلمات المرور مشفرة bcrypt — غيّرها فور النشر)
INSERT INTO users (id, username, password, role, full_name, email, phone, bio, years_of_experience, license_number, status, agree_to_terms, created_at) VALUES
(1, 'admin',      '$2y$12$68oKZgL5QA97ECSNYUy41.7HaEV6hvD49FMZRMvixLWjzHxVrqagm', 'admin',  'مدير النظام',        'admin@example.com',    NULL,     NULL, 0, NULL, 'active', 1, '2026-06-01 09:00:00'),
(2, 'sanaa_travel','$2y$12$vifeRQYboDIUh.dEn8yx0e9LcNH1jz/p7ucdK1INWZNUPAbqs/Q4e', 'agent',  'مكتب صنعاء للسياحة', 'sanaa@example.com',    '0771234567', 'متخصصون في الرحلات الثقافية والتاريخية في العاصمة القديمة، خبرة طويلة في تنظيم الجولات العائلية والدينية.', 10, 'SR-1023', 'active', 1, '2026-06-10 10:30:00'),
(3, 'aden_travel', '$2y$12$vifeRQYboDIUh.dEn8yx0e9LcNH1jz/p7ucdK1INWZNUPAbqs/Q4e', 'agent',  'عدن ترافل',           'aden@example.com',     '0737654321', 'نظم رحلات ساحلية على مدار السنة — عدن والمكلا، برامج عائلية ومغامرات بحرية.', 7, 'AD-4521', 'active', 1, '2026-06-15 14:00:00'),
(4, 'ahmed',      '$2y$12$dS3zdbW7s/jalONonEeIp.0sI7oych0R1U4CWX7pfoZI1oBGnFWmW', 'client', 'أحمد علي',           'ahmed@example.com',    '0711112223', NULL, 0, NULL, 'active', 1, '2026-07-01 08:00:00'),
(5, 'fatima',     '$2y$12$dS3zdbW7s/jalONonEeIp.0sI7oych0R1U4CWX7pfoZI1oBGnFWmW', 'client', 'فاطمة سالم',         'fatima@example.com',   '0722223334', NULL, 0, NULL, 'active', 1, '2026-07-05 12:00:00');

-- ---------- الوجهات ----------
INSERT INTO destinations (id, name, country, description, created_at) VALUES
(1, 'صنعاء', 'اليمن', 'عاصمة اليمن التاريخية، مدينة الحجر والطين — عجيبة الدنيا المعمارية', '2026-06-01 09:00:00'),
(2, 'عدن', 'اليمن', 'المدينة الساحلية الخلابة على بحر العرب، مدينة الشمس', '2026-06-01 09:00:00'),
(3, 'سيئون', 'اليمن', 'مدينة الوادي والسدود التاريخية في حضرموت', '2026-06-01 09:00:00'),
(4, 'المكلا', 'اليمن', 'مدينة الشواطئ الجميلة ومنتجعات البحر الأحمر', '2026-06-01 09:00:00'),
(5, 'تريم', 'اليمن', 'مدينة العلم والأدب والتاريخ في وادي حضرموت', '2026-06-01 09:00:00');

-- ---------- الباقات ----------
INSERT INTO packages (id, title, description, destination, duration, type, price, includes, excludes, itinerary, image, status, agent_id, views, created_at) VALUES
(1, 'جولة صنعاء القديمة: سحر التاريخ والعمران',
 'رحلة ثقافية إلى المدينة القديمة المسجلة في قائمة اليونسكو، زيارة لأبرز المساجد والقصور والبيوت التاريخية مع مرشد متخصص.',
 'صنعاء', 3, 'cultural', 250000,
 'الإقامة في فندق 4 نجوم (3 ليالي)\nوجبة الإفطار يومياً\nمرشد سياحي متخصص طوال الرحلة\nالمواصلات الداخلية\nزيارة قبة الصلوات ومسجد النبو',
 'التذاكر الدولية\nالمصروفات الشخصية\nالتأمين على السفر',
 'اليوم 1: الوصول والاستقبال، جولة مسائية في شارع الزبيري وسوق الصميل\nاليوم 2: المسجد الكبير وقبة الصلوات وقصر السلام، مساءً جولة في البيوت العائلية\nاليوم 3: الزيارة إلى خربة رازح (قرية صخرية محفورة في الجبل)، المغادرة',
 'sample-1.svg', 'available', 2, 142, '2026-07-01 09:00:00'),
(2, 'عطلت عدن الساحلية: الكورنيش وكهف الحوت',
 'استرخاء على شواطئ عدن الذهبية، رحلة بحرية إلى كهف الحوت، وتسوق في سوق عدن الشعبي — مناسبة للعائلات والأصدقاء.',
 'عدن', 4, 'beach', 320000,
 'الإقامة في فندق 5 نجوم على الكورنيش (4 ليالي)\nالإفطار والغداء يومياً\nرحلة بحرية إلى كهف الحوت\nاستقبال من المطار\nجولة سوق عدن',
 'التذاكر الدولية\nالمصروفات الشخصية',
 'اليوم 1: الوصول والاستقبال في فندق الكورنيش، مساءً جولة على الواجهة البحرية\nاليوم 2: رحلة بحرية إلى كهف الحوت، استراحة شاطئية\nاليوم 3: جولة سوق عدن الشعبي وزيارة قلعة صيرة، عشاء ساحلي\nاليوم 4: وقت حر، المغادرة',
 'sample-2.svg', 'available', 3, 98, '2026-07-05 10:00:00'),
(3, 'سيئون وواديها: واحة الهدوء',
 'تصوير غروب في وادي حضرموت، زيارة السدود التاريخية والحضارة الحميرية — رحلة عائلية هادئة بعيداً عن الزحام.',
 'سيئون', 3, 'family', 180000,
 'الإقامة في فندق واحة سيئون (3 ليالي)\nجميع الوجبات\nسيارة خاصة بسائق\nتذاكر المواقع الأثرية',
 'التذاكر الدولية\nالمصروفات الشخصية',
 'اليوم 1: الوصول، جولة في وادي سيئون وسد وادي\nاليوم 2: زيارة معبد وادي حضرموت ومقابر الغمدان، تصوير في الجبال\nاليوم 3: مغادرة مع ضيافة يمنية تقليدية',
 'sample-3.svg', 'available', 2, 76, '2026-07-10 11:00:00'),
(4, 'رحلة المكلا: حيث تلتقي الجبال والبحر',
 'خمسة أيام على شواطئ المكلا، رحلة إلى منتزه الشاطئ، وجولة جبلية في وادي دوعن — تجربة ساحلية متكاملة.',
 'المكلا', 5, 'family', 400000,
 'الإقامة في منتجع ساحلي (5 ليالي)\nالإفطار والعشاء\nرحلة بحرية عائلية\nجولة وادي دوعن\nاستقبال ومطار',
 'التذاكر الدولية\nالمصروفات الشخصية\nالتصوير الجوي',
 'اليوم 1: الوصول والاستقبال، جولة مسائية على الكورنيش\nاليوم 2: رحلة بحرية عائلية مع استراحة غداء\nاليوم 3: جولة وادي دوعن ومناخه الفريد\nاليوم 4: اليوم الشاطئي — وقت حر مع أنشطة منتجع\nاليوم 5: التسوق والتذكارات، المغادرة',
 'sample-4.svg', 'available', 3, 61, '2026-07-12 09:30:00'),
(5, 'تريم: مدينة العلم والتاريخ',
 'رحلة قصيرة إلى قلب حضرموت العلمي، زيارة جامع المظفر والمكتبة العتيقة وأسواق الحرف اليدوية.',
 'تريم', 2, 'cultural', 120000,
 'الإقامة (ليلة واحدة) في ريف تريم\nالإفطار والغداء\nمرشد محلي متخصص\nالمواصلات',
 'التذاكر الدولية\nالمصروفات الشخصية',
 'اليوم 1: الوصول، زيارة جامع المظفر المكتبة العتيقة، سوق الحرف\nاليوم 2: جولة الريف والمزارع، المغادرة',
 'sample-5.svg', 'available', 2, 45, '2026-07-15 14:00:00'),
(6, 'رحلة المعالم الدينية: صنعاء القديمة',
 'برنامج ديني هادئ يشمل أهم المعالم الدينية في صنعاء القديمة مع مرشد شرحي — مناسب للمجموعات والعائلات.',
 'صنعاء', 4, 'religious', 275000,
 'الإقامة في فندق 4 نجوم (4 ليالي)\nالإفطار والغداء\nمرشد شرحي متخصص\nالمواصلات الداخلية\nجولة مساجد العاصمة',
 'التذاكر الدولية\nالمصروفات الشخصية',
 'اليوم 1: الوصول والاستقبال، تعارف وساعة صلاة جماعة\nاليوم 2: المسجد الكبير ورواياته التاريخية، جولة المساجد العتيقة\nاليوم 3: زيارة قبة الصلوات والأماكن الروحانية، محاضرة تاريخية\nاليوم 4: جولة أخيرة، هدايا تذكارية، المغادرة',
 'sample-6.svg', 'available', 2, 89, '2026-07-20 08:00:00');

-- ---------- الحجوزات ----------
INSERT INTO bookings (id, package_id, client_id, booking_date, number_of_travelers, total_price, status, special_requests, created_at) VALUES
(1, 2, 4, '2026-08-15', 2, 640000.00, 'approved',  'نرجو غرفة متجاورة، ووجبات بحرية عند الإمكان', '2026-08-01 10:00:00'),
(2, 1, 5, '2026-09-20', 4, 1000000.00, 'pending', 'عائلة من 4 أفراد — نحتاج جولة هادئة', '2026-08-25 15:30:00'),
(3, 3, 4, '2026-07-10', 3, 540000.00, 'completed', NULL, '2026-06-28 09:00:00');

-- ---------- التقييمات ----------
INSERT INTO reviews (package_id, client_id, rating, comment, created_at) VALUES
(2, 4, 5, 'تجربة استثنائية! الكورنيش وكهف الحوت تفوقا توقعاتي، والفندق راقي جداً. أنصح كل عائلة بزيارة عدن عبر هذا المكتب.', '2026-08-18 12:00:00'),
(2, 5, 4, 'رحلة جميلة والتنظيم ممتاز، أتمنى فقط أن يكون اليوم البحري أطول قليلاً.', '2026-08-19 09:00:00'),
(3, 4, 5, 'أهدأ رحلة قمت بها — الوادي والجدول والمناخ يجعلك تنسى المدينة. المكتب نظم كل شيء بدقة.', '2026-07-15 18:00:00'),
(1, 5, 5, 'صنعاء القديمة تحفة فعلية، والمرشد كان خبيراً في التفاصيل التاريخية. تجربة تستحق كل ريال.', '2026-07-25 10:00:00');
