-- UTF8 completo para tildes/ñ/emojis
SET NAMES utf8mb4;
SET time_zone = "+00:00";

-- 1) Users (si vas a usar login en Laravel)
CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  email VARCHAR(120) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2) Brands
CREATE TABLE brands (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL UNIQUE,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3) Categories (fruta, dulce, bebida, menta, especia, mix...)
CREATE TABLE categories (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4) Flavors (MVP, con ingredientes en texto)
CREATE TABLE flavors (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

  brand_id BIGINT UNSIGNED NOT NULL,
  category_id BIGINT UNSIGNED NULL,

  name VARCHAR(180) NOT NULL,
  description TEXT NULL,

  tobacco_type ENUM('rubio','negro','herbal','sin_nicotina') NULL,

  -- Para buscar "sandía", "chocolate", etc. sin normalizar aún
  ingredients_text VARCHAR(500) NULL,

  -- Guarda URL o ruta (no BLOB)
  image_url VARCHAR(500) NULL,

  created_by BIGINT UNSIGNED NULL,
  is_public TINYINT(1) NOT NULL DEFAULT 1,

  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,

  CONSTRAINT fk_flavors_brand FOREIGN KEY (brand_id) REFERENCES brands(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_flavors_category FOREIGN KEY (category_id) REFERENCES categories(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_flavors_created_by FOREIGN KEY (created_by) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_flavors_brand ON flavors(brand_id);
CREATE INDEX idx_flavors_category ON flavors(category_id);
CREATE INDEX idx_flavors_name ON flavors(name);

-- 5) Mixes (mezclas guardadas)
CREATE TABLE mixes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,

  name VARCHAR(180) NOT NULL,
  notes TEXT NULL,

  -- por si queréis compartir mezclas públicas
  is_public TINYINT(1) NOT NULL DEFAULT 0,

  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,

  CONSTRAINT fk_mixes_user FOREIGN KEY (user_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_mixes_user ON mixes(user_id);

-- 6) Mix items (qué sabores lleva cada mezcla + proporción)
-- ratio: puedes guardar porcentaje (ej 70.00) o "partes" (ej 2.00, 1.00).
CREATE TABLE mix_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

  mix_id BIGINT UNSIGNED NOT NULL,
  flavor_id BIGINT UNSIGNED NOT NULL,

  ratio DECIMAL(5,2) NULL,

  role ENUM('base','toque','frio','otro') NULL,

  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,

  CONSTRAINT fk_mixitems_mix FOREIGN KEY (mix_id) REFERENCES mixes(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_mixitems_flavor FOREIGN KEY (flavor_id) REFERENCES flavors(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_mixitems_mix ON mix_items(mix_id);
CREATE INDEX idx_mixitems_flavor ON mix_items(flavor_id);

-- Evita duplicar el mismo sabor en una mezcla (opcional)
ALTER TABLE mix_items
  ADD UNIQUE KEY uq_mix_flavor (mix_id, flavor_id);

-- 7) Favoritos (opcional, pero muy útil)
CREATE TABLE favorites (
  user_id BIGINT UNSIGNED NOT NULL,
  flavor_id BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NULL,
  PRIMARY KEY (user_id, flavor_id),

  CONSTRAINT fk_fav_user FOREIGN KEY (user_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_fav_flavor FOREIGN KEY (flavor_id) REFERENCES flavors(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;