CREATE DATABASE IF NOT EXISTS aromaesabor_db;
USE aromaesabor_db;

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    slug VARCHAR(100) UNIQUE NOT NULL,
    image VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    price INT NOT NULL COMMENT 'Preço em centavos',
    category_id INT NOT NULL,
    image VARCHAR(500),
    stock INT DEFAULT 0,
    slug VARCHAR(150) UNIQUE NOT NULL,
    featured TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    INDEX idx_category (category_id),
    INDEX idx_featured (featured)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150),
    type_user varchar(10),
    email VARCHAR(150) UNIQUE,
    password VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    UNIQUE KEY unique_user_product (user_id, product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_price INT NOT NULL COMMENT 'Preço em centavos',
    status VARCHAR(50) DEFAULT 'pending',
    shipping_address TEXT,
    billing_address TEXT,
    payment_method VARCHAR(100),
    tracking_number VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


INSERT INTO categories (name, description, slug, image) VALUES
('Chás Verdes', 'Chás verdes frescos e revitalizantes', 'cha-verde', 'https://imgs.search.brave.com/93RZxUIMV3SIBA_kJS3pTvDalyfzHUNR4EZSj7Bryc8/rs:fit:500:0:1:0/g:ce/aHR0cHM6Ly9tZWRp/YS5nZXR0eWltYWdl/cy5jb20vaWQvMTg0/OTQ4Nzk3L3B0L2Zv/dG8vY2glQzMlQTEt/dmVyZGUuanBnP3M9/NjEyeDYxMiZ3PTAm/az0yMCZjPTZzQkZY/WkNfd2lVTHlNZ3lC/OFZsdDlreEw0ZUp6/Q2d4ZE9JLTVENGdr/bDA9'),
('Chás Pretos', 'Chás pretos robustos e encorpados', 'cha-preto', 'https://image.tuasaude.com/media/article/rv/dc/beneficios-do-cha-preto_32416.jpg?width=468&height=312&mode=crop&anchor=middlecenter'),
('Chás Brancos', 'Chás brancos delicados e suaves', 'cha-branco', 'https://imgs.search.brave.com/KG8g-KMOPI_bVM3rL69e-SA96vCzRlPEh75GGfeuzPU/rs:fit:860:0:0:0/g:ce/aHR0cHM6Ly9mbHku/bWV0cm9pbWcuY29t/L3VwbG9hZC9xXzg1/LHdfNzAwL2h0dHBz/Oi8vdXBsb2Fkcy5t/ZXRyb2ltZy5jb20v/d3AtY29udGVudC91/cGxvYWRzLzIwMjQv/MTAvMTUxMTMwMjEv/eGljYXJhLWNvbS1j/aGEtZGUtY2Ftb21p/bGEuanBn'),
('Chás Oolong', 'Chás oolong com sabor único e complexo', 'cha-oolong', 'https://imgs.search.brave.com/d0IvVKnuKtS9bxC8f1aMqlePQuOKuR3Gt0zf_cMAztg/rs:fit:860:0:0:0/g:ce/aHR0cHM6Ly9jZG4u/b2FudGFnb25pc3Rh/LmNvbS91cGxvYWRz/LzIwMjQvMTEvQ2hh/LU9vbG9uZ18xNzMy/NTk2MjIwNDM4LTEw/MjR4NTc2LmpwZw'),
('Chás de Ervas', 'Chás de ervas naturais e aromáticos', 'cha-ervas', 'https://imgs.search.brave.com/SK_8U72cgsl18baaQcaNGsUDcoUJ4djLeZRRhtMqXHk/rs:fit:860:0:0:0/g:ce/aHR0cHM6Ly9jYXNh/LmFicmlsLmNvbS5i/ci93cC1jb250ZW50/L3VwbG9hZHMvMjAy/MS8wNi8xOS1lcnZh/cy1wYXJhLXBsYW50/YXItZS1mYXplci1j/aGElQ0MlODFzLWlz/dG9jay0xOC5wbmc_/dz03NzU');


INSERT INTO products (name, description, price, category_id, image, stock, slug, featured) VALUES
-- Chás Verdes (category_id = 1)
('Matcha Premium', 'Pó de chá verde matcha de alta qualidade, perfeito para cerimônias', 4500, 1, 'https://imgs.search.brave.com/fX6SgDziC2nqnqsoaCGF1ra5s43t18z0_59UVuBTsSE/rs:fit:860:0:0:0/g:ce/aHR0cHM6Ly9zMi1v/Z2xvYm8uZ2xiaW1n/LmNvbS94V2luOUNT/Y2cwZmQzeXlleEww/eEw1Mk5TdGs9LzB4/MDo2MjQweDQxNjAv/ODg4eDAvc21hcnQv/ZmlsdGVyczpzdHJp/cF9pY2MoKS9pLnMz/LmdsYmltZy5jb20v/djEvQVVUSF9kYTAy/NTQ3NGMwYzQ0ZWRk/OTkzMzJkZGRiMDlj/YWJlOC9pbnRlcm5h/bF9waG90b3MvYnMv/MjAyMy90L1gvUldM/VmIwVEIyOFRPTUVi/clJDaHcvZ3JlZW4t/cGFuY2FrZXMtd2l0/aC1tYXRjaGEtcG93/ZGVyLXdpdGgtcmVk/LWphbS10b3Atdmll/dy0xLS5qcGc', 50, 'matcha-premium', 1),
('Sencha Japonês', 'Chá verde japonês com sabor fresco e vegetal', 3200, 1, 'https://imgs.search.brave.com/bkRJ6X-i-9m42ve000LKuKfbW1BvzqaT39PN1eURs80/rs:fit:860:0:0:0/g:ce/aHR0cHM6Ly93d3cu/aW50aS10ZWEuY29t/L2Nkbi9zaG9wL2Zp/bGVzL2luLXQtaGVi/cmFzLXRlLWxhdGEt/Z3JlZW4tdGVhLXZl/cmRlLXNlbmNoYS1h/Z3VhLTgwZ183MDB4/NzAwLnBuZz92PTE3/MjA2MjI1MDM', 40, 'sencha-japones', 1),
('Gyokuro Especial', 'Chá verde premium cultivado à sombra', 5500, 1, 'https://imgs.search.brave.com/m7tK1agoAzeZxLJnKNEbYbjjVoE82VrXW7zqRvZcdFk/rs:fit:860:0:0:0/g:ce/aHR0cHM6Ly9tLm1l/ZGlhLWFtYXpvbi5j/b20vaW1hZ2VzL0kv/NjFhSWtCaWFqVkwu/anBn', 30, 'gyokuro-especial', 0),

-- Chás Pretos (category_id = 2)
('Assam Indiano', 'Chá preto robusto e malty da Índia', 2800, 2, 'https://imgs.search.brave.com/ONkpm_0PEOlJpn-tzEVfenbNPbVDepJmqf-Av7Pwb4c/rs:fit:860:0:0:0/g:ce/aHR0cHM6Ly9tZWRp/YS5pc3RvY2twaG90/by5jb20vaWQvMTcy/NDM3MTQzL3Bob3Rv/L2Fzc2FtLXRlYS1s/ZWF2ZXMtc3RyYWlu/aW5nLWluLWRpZmZ1/c2VyLW92ZXItY3Vw/LWFuZC1zYXVjZXIu/d2VicD9hPTEmYj0x/JnM9NjEyeDYxMiZ3/PTAmaz0yMCZjPTFH/dkFGN1VRaWswR0k4/MnlYVjlJUWcyV3dI/VUlkZVJJWHBHWVll/cTY2ZE09', 60, 'assam-indiano', 1),
('Darjeeling Primeira Colheita', 'Chá preto delicado com notas florais', 4200, 2, 'https://imgs.search.brave.com/nI_KvLVpqJrlZrtDliiMME_8EWI-WY76ufIBxJX_YJU/rs:fit:860:0:0:0/g:ce/aHR0cHM6Ly90ZWFz/aG9wLmNvbS5ici9j/ZG4vc2hvcC9maWxl/cy9kYXJqZWVsaW5n/LXRlZXN0YS12YWxs/ZXktYW5kLWdpZWxs/ZS1mdGdmb3AxLWZp/cnN0LWZsdXNoLXRl/YS1zaG9wLTUyNjYy/MTYuanBnP3Y9MTc1/Mzc5MzI2NSZ3aWR0/aD05NjA', 35, 'darjeeling-primeira', 1), -- CORRIGIDO: Adicionado 35 e 'darjeeling-primeira'
('Keemun Chinês', 'Chá preto chinês com sabor frutado', 3800, 2, 'https://imgs.search.brave.com/haDIeK9A_7uMiIzRI29JA6wCqrrj0IlEjPeQORL9WyI/rs:fit:860:0:0:0/g:ce/aHR0cHM6Ly9kMjdw/Y2xsMmR4OTd2di5j/bG91ZGZyb250Lm5l/dC9pbmZvL3dwLWNv/bnRlbnQvdXBsb2Fk/cy8yMDE1LzEwL21v/YmlsZV9LZWVtdW4t/YmxhY2stdGVhMy5q/cGc', 45, 'keemun-chines', 0),

-- Chás Brancos (category_id = 3)
('Silver Needle', 'Chá branco delicado com apenas brotos de prata', 5800, 3, 'https://imgs.search.brave.com/kL2PZDs_SC1Q6wTULqGf9_gyZvTpSAGeaUzhoGzBrcQ/rs:fit:860:0:0:0/g:ce/aHR0cHM6Ly9tLm1l/ZGlhLWFtYXpvbi5j/b20vaW1hZ2VzL0kv/ODFMRzQyUFBPUEwu/anBn', 25, 'silver-needle', 1),
('White Peony', 'Chá branco suave com notas florais', 4500, 3, 'https://imgs.search.brave.com/60MTStGpKy21lbE2byT_ZnG0uZd-zFSF997L1qrowJo/rs:fit:860:0:0:0/g:ce/aHR0cHM6Ly9jZG4x/MS5iaWdjb21tZXJj/ZS5jb20vcy15M2Y2/MnhydzQ1L2ltYWdl/cy9zdGVuY2lsLzUw/MHg1MDAvcHJvZHVj/dHMvMzM1LzE1Lzcu/X1doaXRlX1Blb255/X09yZ2FuaWNfQ2hp/bmVzZV9XaGl0ZV9U/ZWFfMW96XzJfTGVh/Zl8yX181MDE4OC4x/NTc0MDY0NDcxLmpw/Zz9jPTE', 40, 'white-peony', 0),

-- Chás Oolong (category_id = 4)
('Tie Guan Yin', 'Oolong chinês com aroma floral intenso', 6200, 4, 'https://imgs.search.brave.com/jpKUKAOZdj5xbyxomt-EkSqZ630QEfQJA17m4ehD1Sk/rs:fit:860:0:0:0/g:ce/aHR0cHM6Ly9tLm1l/ZGlhLWFtYXpvbi5j/b20vaW1hZ2VzL0kv/NzEzOFJwOCtnbEwu/anBn', 20, 'tie-guan-yin', 1),
('Da Hong Pao', 'Oolong premium com sabor complexo', 7000, 4, 'https://imgs.search.brave.com/J5Vh_kfi3oNV-_cfsw0j10G6PzaZO7lGEcSW8aca1sg/rs:fit:860:0:0:0/g:ce/aHR0cHM6Ly9tLm1l/ZGlhLWFtYXpvbi5j/b20vaW1hZ2VzL0kv/NTFETkxuSE9CTkwu/anBn', 15, 'da-hong-pao', 0),

-- Chás de Ervas (category_id = 5)
('Camomila Premium', 'Chá de camomila pura para relaxamento', 2200, 5, 'https://imgs.search.brave.com/4TE-9iun1YioQjbynqlLsHbP09YiOEB_KDakbxLCpbw/rs:fit:860:0:0:0/g:ce/aHR0cHM6Ly9odHRw/Mi5tbHN0YXRpYy5j/b20vRF9OUV9OUF84/OTg2MTUtTUxVNzM0/MTgwNjA1MzNfMTIy/MDIzLU8ud2VicA', 70, 'camomila-premium', 1),
('Menta Fresca', 'Chá de menta refrescante e natural', 1800, 5, 'https://imgs.search.brave.com/WSl3E8BVtfEJhMvWemYQYD6mvUovOwU11hRBikRs3_I/rs:fit:860:0:0:0/g:ce/aHR0cHM6Ly9wMi50/cnJzZi5jb20vaW1h/Z2UvZmdldC9jZi83/NzQvMC9pbWFnZXMu/dGVycmEuY29tLzIw/MjMvMDcvMjYvMTY5/MDc4NjU5OC1tZW50/YS1wcm9wcmllZGFk/ZXMtZG8tY2hhLXJl/ZnJlc2NhbnRlLXZh/by1hbGVtLWRhcy1k/aWdlc3RpdmFzLmpw/Zw', 80, 'menta-fresca', 0),
('Gengibre e Limão', 'Blend aquecedor com gengibre e limão', 2500, 5, 'https://imgs.search.brave.com/6Uofw7Fkfyh_JmskaH61ju75_gEY-AW5VJDCGaBtVPs/rs:fit:860:0:0:0/g:ce/aHR0cHM6Ly9zdGF0/aWMuaXRkZy5jb20u/YnIvaW1hZ2VzLzY0/MC1hdXRvLzBlN2Vk/MjRlNThiYTBhNjlj/ZWFlYjc1NTAxNWY0/ZDJiL3NodXR0ZXJz/dG9jay0zMDAyODY5/NDAtMS0uanBn', 55, 'gengibre-limao', 0);

ALTER TABLE `aromaesabor_db`.`orders`
ADD COLUMN items JSON;

-- Inserir usuários de teste
INSERT INTO users (name, type_user, email, password) VALUES
('João Silva', 'cliente', 'joao@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'), -- senha: password
('Maria Santos', 'cliente', 'maria@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'), -- senha: password
('Admin User', 'admin', 'admin@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'); -- senha: password