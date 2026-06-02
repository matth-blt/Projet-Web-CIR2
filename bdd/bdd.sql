-- ----------------------------------------------------------
-- Script MYSQL pour mcd 
-- ----------------------------------------------------------


-- ----------------------------
-- Table: horaire
-- ----------------------------
CREATE TABLE horaire (
  heure VARCHAR(50) NOT NULL,
  CONSTRAINT horaire_PK PRIMARY KEY (heure)
)ENGINE=InnoDB;


-- ----------------------------
-- Table: departement
-- ----------------------------
CREATE TABLE departement (
  code_dep INT NOT NULL,
  nom_departement VARCHAR(50) NOT NULL,
  CONSTRAINT departement_PK PRIMARY KEY (code_dep)
)ENGINE=InnoDB;


-- ----------------------------
-- Table: paiement
-- ----------------------------
CREATE TABLE paiement (
  type_paiement VARCHAR(50) NOT NULL,
  CONSTRAINT paiement_PK PRIMARY KEY (type_paiement)
)ENGINE=InnoDB;


-- ----------------------------
-- Table: prise
-- ----------------------------
CREATE TABLE prise (
  type_prise VARCHAR(50) NOT NULL,
  CONSTRAINT prise_PK PRIMARY KEY (type_prise)
)ENGINE=InnoDB;


-- ----------------------------
-- Table: condition_acces
-- ----------------------------
CREATE TABLE condition_acces (
  pdc_condition VARCHAR(50) NOT NULL,
  CONSTRAINT condition_acces_PK PRIMARY KEY (pdc_condition)
)ENGINE=InnoDB;


-- ----------------------------
-- Table: implantation
-- ----------------------------
CREATE TABLE implantation (
  implantation_station VARCHAR(50) NOT NULL,
  CONSTRAINT implantation_PK PRIMARY KEY (implantation_station)
)ENGINE=InnoDB;


-- ----------------------------
-- Table: enseigne
-- ----------------------------
CREATE TABLE enseigne (
  nom_enseigne VARCHAR(50) NOT NULL,
  CONSTRAINT enseigne_PK PRIMARY KEY (nom_enseigne)
)ENGINE=InnoDB;


-- ----------------------------
-- Table: acteur
-- ----------------------------
CREATE TABLE acteur (
  id_acteur INT NOT NULL AUTO_INCREMENT,
  siren_acteur INT NOT NULL,
  nom_acteur VARCHAR(50),
  contact_acteur VARCHAR(255),
  telephone_acteur VARCHAR(50),
  role_acteur VARCHAR(50) NOT NULL,
  CONSTRAINT acteur_PK PRIMARY KEY (id_acteur)
)ENGINE=InnoDB;


-- ----------------------------
-- Table: commune
-- ----------------------------
CREATE TABLE commune (
  code_insee_commune INT NOT NULL,
  nom_commune VARCHAR(50) NOT NULL,
  code_dep INT,
  CONSTRAINT commune_PK PRIMARY KEY (code_insee_commune),
  CONSTRAINT commune_code_dep_FK FOREIGN KEY (code_dep) REFERENCES departement (code_dep)
)ENGINE=InnoDB;


-- ----------------------------
-- Table: point_de_charge
-- ----------------------------
CREATE TABLE point_de_charge (
  id_pdc INT NOT NULL,
  lon FLOAT NOT NULL,
  lat FLOAT NOT NULL,
  puissance FLOAT NOT NULL,
  cable_t2_attache TINYINT(1),
  gratuit TINYINT(1),
  pdc_condition VARCHAR(50),
  CONSTRAINT point_de_charge_PK PRIMARY KEY (id_pdc),
  CONSTRAINT point_de_charge_pdc_condition_FK FOREIGN KEY (pdc_condition) REFERENCES condition_acces (pdc_condition)
)ENGINE=InnoDB;


-- ----------------------------
-- Table: est_payer_avec
-- ----------------------------
CREATE TABLE est_payer_avec (
  type_paiement VARCHAR(50) NOT NULL,
  id_pdc INT NOT NULL,
  CONSTRAINT est_payer_avec_PK PRIMARY KEY (type_paiement, id_pdc),
  CONSTRAINT est_payer_avec_type_paiement_FK FOREIGN KEY (type_paiement) REFERENCES paiement (type_paiement),
  CONSTRAINT est_payer_avec_id_pdc_FK FOREIGN KEY (id_pdc) REFERENCES point_de_charge (id_pdc)
)ENGINE=InnoDB;


-- ----------------------------
-- Table: a_des
-- ----------------------------
CREATE TABLE a_des (
  id_pdc INT NOT NULL,
  type_prise VARCHAR(50) NOT NULL,
  CONSTRAINT a_des_PK PRIMARY KEY (id_pdc, type_prise),
  CONSTRAINT a_des_id_pdc_FK FOREIGN KEY (id_pdc) REFERENCES point_de_charge (id_pdc),
  CONSTRAINT a_des_type_prise_FK FOREIGN KEY (type_prise) REFERENCES prise (type_prise)
)ENGINE=InnoDB;


-- ----------------------------
-- Table: station
-- ----------------------------
CREATE TABLE station (
  id_station_itinerance VARCHAR(50) NOT NULL,
  nom_station VARCHAR(50) NOT NULL,
  adresse_station VARCHAR(50) NOT NULL,
  nbr_pdc INT NOT NULL,
  date_mise_en_service DATE,
  racordement TINYINT(1),
  id_station_local VARCHAR(50),
  code_insee_commune INT,
  id_acteur INT,
  implantation_station VARCHAR(50),
  nom_enseigne VARCHAR(50),
  id_acteur_est_utiliser_par INT,
  CONSTRAINT station_PK PRIMARY KEY (id_station_itinerance),
  CONSTRAINT station_code_insee_commune_FK FOREIGN KEY (code_insee_commune) REFERENCES commune (code_insee_commune),
  CONSTRAINT station_id_acteur_FK FOREIGN KEY (id_acteur) REFERENCES acteur (id_acteur),
  CONSTRAINT station_implantation_station_FK FOREIGN KEY (implantation_station) REFERENCES implantation (implantation_station),
  CONSTRAINT station_nom_enseigne_FK FOREIGN KEY (nom_enseigne) REFERENCES enseigne (nom_enseigne),
  CONSTRAINT station_id_acteur_est_utiliser_par_FK FOREIGN KEY (id_acteur_est_utiliser_par) REFERENCES acteur (id_acteur)
)ENGINE=InnoDB;


-- ----------------------------
-- Table: a_une
-- ----------------------------
CREATE TABLE a_une (
  heure VARCHAR(50) NOT NULL,
  id_station_itinerance VARCHAR(50) NOT NULL,
  CONSTRAINT a_une_PK PRIMARY KEY (heure, id_station_itinerance),
  CONSTRAINT a_une_heure_FK FOREIGN KEY (heure) REFERENCES horaire (heure),
  CONSTRAINT a_une_id_station_itinerance_FK FOREIGN KEY (id_station_itinerance) REFERENCES station (id_station_itinerance)
)ENGINE=InnoDB;


-- ----------------------------
-- Table: possede_des
-- ----------------------------
CREATE TABLE possede_des (
  id_pdc INT NOT NULL,
  id_station_itinerance VARCHAR(50) NOT NULL,
  CONSTRAINT possede_des_PK PRIMARY KEY (id_pdc, id_station_itinerance),
  CONSTRAINT possede_des_id_pdc_FK FOREIGN KEY (id_pdc) REFERENCES point_de_charge (id_pdc),
  CONSTRAINT possede_des_id_station_itinerance_FK FOREIGN KEY (id_station_itinerance) REFERENCES station (id_station_itinerance)
)ENGINE=InnoDB;

