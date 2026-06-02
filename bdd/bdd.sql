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
-- Table: type_paiement
-- ----------------------------
CREATE TABLE type_paiement (
  type_paiement VARCHAR(50) NOT NULL,
  CONSTRAINT type_paiement_PK PRIMARY KEY (type_paiement)
)ENGINE=InnoDB;


-- ----------------------------
-- Table: departement
-- ----------------------------
CREATE TABLE departement (
  code_dep INT NOT NULL,
  CONSTRAINT departement_PK PRIMARY KEY (code_dep)
)ENGINE=InnoDB;


-- ----------------------------
-- Table: paiement
-- ----------------------------
CREATE TABLE paiement (
  id_paiement INT NOT NULL AUTO_INCREMENT,
  gratuit TINYINT(1),
  CONSTRAINT paiement_PK PRIMARY KEY (id_paiement)
)ENGINE=InnoDB;


-- ----------------------------
-- Table: condition_acces
-- ----------------------------
CREATE TABLE condition_acces (
  pdc_condition VARCHAR(50) NOT NULL,
  CONSTRAINT condition_acces_PK PRIMARY KEY (pdc_condition)
)ENGINE=InnoDB;


-- ----------------------------
-- Table: type_de_prise
-- ----------------------------
CREATE TABLE type_de_prise (
  type_prise VARCHAR(50) NOT NULL,
  CONSTRAINT type_de_prise_PK PRIMARY KEY (type_prise)
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
-- Table: amenageur
-- ----------------------------
CREATE TABLE amenageur (
  siren_amenageur INT NOT NULL,
  nom_amenageur VARCHAR(50),
  contact_amenageur VARCHAR(50),
  CONSTRAINT amenageur_PK PRIMARY KEY (siren_amenageur)
)ENGINE=InnoDB;


-- ----------------------------
-- Table: operateur
-- ----------------------------
CREATE TABLE operateur (
  nom_operateur VARCHAR(50) NOT NULL,
  contact_operateur VARCHAR(50) NOT NULL,
  telephone_operateur VARCHAR(50),
  CONSTRAINT operateur_PK PRIMARY KEY (nom_operateur)
)ENGINE=InnoDB;


-- ----------------------------
-- Table: commune
-- ----------------------------
CREATE TABLE commune (
  code_insee_commune INT NOT NULL,
  code_dep INT,
  CONSTRAINT commune_PK PRIMARY KEY (code_insee_commune),
  CONSTRAINT commune_code_dep_FK FOREIGN KEY (code_dep) REFERENCES departement (code_dep)
)ENGINE=InnoDB;


-- ----------------------------
-- Table: est_paye_en
-- ----------------------------
CREATE TABLE est_paye_en (
  type_paiement VARCHAR(50) NOT NULL,
  id_paiement INT NOT NULL,
  CONSTRAINT est_paye_en_PK PRIMARY KEY (type_paiement, id_paiement),
  CONSTRAINT est_paye_en_type_paiement_FK FOREIGN KEY (type_paiement) REFERENCES type_paiement (type_paiement),
  CONSTRAINT est_paye_en_id_paiement_FK FOREIGN KEY (id_paiement) REFERENCES paiement (id_paiement)
)ENGINE=InnoDB;


-- ----------------------------
-- Table: travaille_pour
-- ----------------------------
CREATE TABLE travaille_pour (
  siren_amenageur INT NOT NULL,
  nom_operateur VARCHAR(50) NOT NULL,
  CONSTRAINT travaille_pour_PK PRIMARY KEY (siren_amenageur, nom_operateur),
  CONSTRAINT travaille_pour_siren_amenageur_FK FOREIGN KEY (siren_amenageur) REFERENCES amenageur (siren_amenageur),
  CONSTRAINT travaille_pour_nom_operateur_FK FOREIGN KEY (nom_operateur) REFERENCES operateur (nom_operateur)
)ENGINE=InnoDB;


-- ----------------------------
-- Table: point_de_charge
-- ----------------------------
CREATE TABLE point_de_charge (
  id_pdc INT NOT NULL,
  puissance FLOAT NOT NULL,
  cable_t2_attache TINYINT(1),
  pdc_condition VARCHAR(50),
  CONSTRAINT point_de_charge_PK PRIMARY KEY (id_pdc),
  CONSTRAINT point_de_charge_pdc_condition_FK FOREIGN KEY (pdc_condition) REFERENCES condition_acces (pdc_condition)
)ENGINE=InnoDB;


-- ----------------------------
-- Table: prise
-- ----------------------------
CREATE TABLE prise (
  id_prise INT NOT NULL AUTO_INCREMENT,
  id_pdc INT NOT NULL,
  type_prise VARCHAR(50) NOT NULL,
  CONSTRAINT prise_PK PRIMARY KEY (id_prise),
  CONSTRAINT prise_id_pdc_FK FOREIGN KEY (id_pdc) REFERENCES point_de_charge (id_pdc),
  CONSTRAINT prise_type_prise_FK FOREIGN KEY (type_prise) REFERENCES type_de_prise (type_prise)
)ENGINE=InnoDB;


-- ----------------------------
-- Table: est_payer_avec
-- ----------------------------
CREATE TABLE est_payer_avec (
  id_paiement INT NOT NULL,
  id_pdc INT NOT NULL,
  CONSTRAINT est_payer_avec_PK PRIMARY KEY (id_paiement, id_pdc),
  CONSTRAINT est_payer_avec_id_paiement_FK FOREIGN KEY (id_paiement) REFERENCES paiement (id_paiement),
  CONSTRAINT est_payer_avec_id_pdc_FK FOREIGN KEY (id_pdc) REFERENCES point_de_charge (id_pdc)
)ENGINE=InnoDB;


-- ----------------------------
-- Table: station
-- ----------------------------
CREATE TABLE station (
  id_station_itinerance VARCHAR(50) NOT NULL,
  nom_station VARCHAR(50) NOT NULL,
  adresse_station VARCHAR(50) NOT NULL,
  nbr_pdc INT NOT NULL,
  lon FLOAT NOT NULL,
  lat FLOAT NOT NULL,
  date_mise_en_service DATE NOT NULL,
  racordement TINYINT(1),
  id_station_local VARCHAR(50),
  code_insee_commune INT,
  siren_amenageur INT,
  nom_operateur VARCHAR(50),
  implantation_station VARCHAR(50),
  nom_enseigne VARCHAR(50),
  CONSTRAINT station_PK PRIMARY KEY (id_station_itinerance),
  CONSTRAINT station_code_insee_commune_FK FOREIGN KEY (code_insee_commune) REFERENCES commune (code_insee_commune),
  CONSTRAINT station_siren_amenageur_FK FOREIGN KEY (siren_amenageur) REFERENCES amenageur (siren_amenageur),
  CONSTRAINT station_nom_operateur_FK FOREIGN KEY (nom_operateur) REFERENCES operateur (nom_operateur),
  CONSTRAINT station_implantation_station_FK FOREIGN KEY (implantation_station) REFERENCES implantation (implantation_station),
  CONSTRAINT station_nom_enseigne_FK FOREIGN KEY (nom_enseigne) REFERENCES enseigne (nom_enseigne)
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
-- Table: a_des
-- ----------------------------
CREATE TABLE a_des (
  id_pdc INT NOT NULL,
  id_station_itinerance VARCHAR(50) NOT NULL,
  CONSTRAINT a_des_PK PRIMARY KEY (id_pdc, id_station_itinerance),
  CONSTRAINT a_des_id_pdc_FK FOREIGN KEY (id_pdc) REFERENCES point_de_charge (id_pdc),
  CONSTRAINT a_des_id_station_itinerance_FK FOREIGN KEY (id_station_itinerance) REFERENCES station (id_station_itinerance)
)ENGINE=InnoDB;

