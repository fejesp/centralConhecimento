SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES,NO_AUTO_VALUE_ON_ZERO';


-- -----------------------------------------------------
-- Table `usuarios`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT 'Identificação interna do usuário',
  `nome` VARCHAR(200) NOT NULL COMMENT 'Nome de exibição do usuário',
  `email` VARCHAR(200) NOT NULL COMMENT 'Email de contato e login no sistema',
  `senha` CHAR(32) NOT NULL COMMENT 'MD5 da senha usada',
  `admin` TINYINT(1) NOT NULL COMMENT 'Indica se o usuário é administrador',
  `ativo` TINYINT(1) NOT NULL COMMENT 'Indica se o usuário está ativo (inativo é usada para EJs desfederadas)',
  `usoMax` INT UNSIGNED NOT NULL COMMENT 'Indica a cota de uso máxima de anexos de posts do usuário (em kiB) (0 = sem limite)',
  `cookie` CHAR(32) NOT NULL COMMENT 'Salva a chave de login salva no cookie',
  PRIMARY KEY (`id`),
  UNIQUE INDEX `email_UNIQUE` (`email` ASC),
  UNIQUE INDEX `cookie_UNIQUE` (`cookie` ASC))
ENGINE = InnoDB
COMMENT = 'Armazena os dados de todos os usuários do sistema';


-- -----------------------------------------------------
-- Table `pastas`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `pastas` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT 'Identificação interna da pasta',
  `nome` VARCHAR(200) NOT NULL COMMENT 'Nome de exibição da pasta',
  `descricao` TEXT NOT NULL,
  `pai` INT NOT NULL COMMENT 'Referencia a pasta pai desta (0 indica pasta raiz)',
  `visibilidade` ENUM('publico','geral','seleto') NOT NULL COMMENT 'Indica visibilidade da pasta',
  `criador` INT NOT NULL COMMENT 'Indica o usuário que criou a pasta',
  PRIMARY KEY (`id`),
  INDEX `pastas_pai` (`pai` ASC),
  INDEX `pastas_criador` (`criador` ASC),
  UNIQUE INDEX `pastas_nomeEPai` (`nome` ASC, `pai` ASC),
  CONSTRAINT `pastas_pai`
    FOREIGN KEY (`pai`)
    REFERENCES `pastas` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `pastas_criador`
    FOREIGN KEY (`criador`)
    REFERENCES `usuarios` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = 'Armazena a estrutura hierárquica das pastas';


-- -----------------------------------------------------
-- Table `posts`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `posts` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT 'Identificação interna',
  `pasta` INT NOT NULL COMMENT 'Referencia a pasta na qual esse post foi publicado',
  `nome` VARCHAR(200) NOT NULL COMMENT 'Título do post',
  `conteudo` TEXT NOT NULL COMMENT 'Conteúdo do post',
  `data` DATETIME NOT NULL COMMENT 'Data da criação do post',
  `modificacao` DATETIME NOT NULL COMMENT 'Data da última modificação do post',
  `visibilidade` ENUM('publico','geral','seleto') NOT NULL COMMENT 'Indica a permeabilidade da visibilidade do post',
  `criador` INT NOT NULL COMMENT 'Referencia o usuário que criou o post',
  PRIMARY KEY (`id`),
  INDEX `posts_pasta` (`pasta` ASC),
  INDEX `posts_criador` (`criador` ASC),
  UNIQUE INDEX `posts_unico` (`pasta` ASC, `nome` ASC),
  CONSTRAINT `posts_pasta`
    FOREIGN KEY (`pasta`)
    REFERENCES `pastas` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `posts_criador`
    FOREIGN KEY (`criador`)
    REFERENCES `usuarios` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = 'Armazena todas as postagens feitas';


-- -----------------------------------------------------
-- Table `anexos`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `anexos` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT 'Identificação do anexo (também é o nome da pasta onde está armazenado)',
  `nome` VARCHAR(200) NOT NULL COMMENT 'Nome original do arquivo',
  `post` INT NOT NULL COMMENT 'Referencia o post no qual está anexado',
  `visibilidade` ENUM('publico','geral','seleto') NOT NULL COMMENT 'Indica a permeabilidade da visibilidade do anexo',
  `tamanho` INT NOT NULL COMMENT 'Tamanho do anexo (em kiB)',
  PRIMARY KEY (`id`),
  INDEX `anexos_post` (`post` ASC),
  UNIQUE INDEX `anexos_unico` (`nome` ASC, `post` ASC),
  CONSTRAINT `anexos_post`
    FOREIGN KEY (`post`)
    REFERENCES `posts` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = 'Lista todos os arquivos anexos usados no sistema';


-- -----------------------------------------------------
-- Table `visibilidades`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `visibilidades` (
  `tipoItem` ENUM('pasta','post','anexo') NOT NULL COMMENT 'Indica o tipo de item',
  `item` INT NOT NULL COMMENT 'Referencia o item',
  `usuario` INT NOT NULL COMMENT 'Referencia o usuário que pode ver o item',
  PRIMARY KEY (`tipoItem`, `item`, `usuario`),
  INDEX `visibilidades_usuario` (`usuario` ASC),
  CONSTRAINT `visibilidades_usuario`
    FOREIGN KEY (`usuario`)
    REFERENCES `usuarios` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = 'Grava as permissões dos itens com visibilidade seleta';


-- -----------------------------------------------------
-- Table `tags`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tags` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT 'Identificação interna',
  `nome` VARCHAR(200) NOT NULL COMMENT 'Nome da tag',
  PRIMARY KEY (`id`),
  UNIQUE INDEX `nome_UNIQUE` (`nome` ASC))
ENGINE = InnoDB
COMMENT = 'Lista todas as tags criadas';


-- -----------------------------------------------------
-- Table `tagsEmPosts`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tagsEmPosts` (
  `post` INT NOT NULL COMMENT 'Referencia o post',
  `tag` INT NOT NULL COMMENT 'Referencia a tag',
  PRIMARY KEY (`post`, `tag`),
  INDEX `tagsEmPosts_post` (`post` ASC),
  INDEX `tagsEmPosts_tag` (`tag` ASC),
  CONSTRAINT `tagsEmPosts_post`
    FOREIGN KEY (`post`)
    REFERENCES `posts` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `tagsEmPosts_tag`
    FOREIGN KEY (`tag`)
    REFERENCES `tags` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = 'Guarda a relação entre tags e posts';


-- -----------------------------------------------------
-- Table `forms`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `forms` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT 'Identificação interna',
  `pasta` INT NOT NULL COMMENT 'Referencia a pasta na qual o formulário irá criar postagens',
  `nome` VARCHAR(200) NOT NULL COMMENT 'Nome do formulário',
  `descricao` TEXT NOT NULL COMMENT 'Descrição do formulário',
  `conteudo` TEXT NOT NULL COMMENT 'Armazena a definição dos campos em JSON',
  `data` DATETIME NOT NULL COMMENT 'Data da criação do form',
  `modificacao` DATETIME NOT NULL COMMENT 'Data da última modificação do form',
  `ativo` TINYINT(1) NOT NULL COMMENT 'Indica se o formulário está aceitando submissões',
  `criador` INT NOT NULL COMMENT 'Referencia o usuário que criou o formulário',
  PRIMARY KEY (`id`),
  INDEX `formularios_pasta` (`pasta` ASC),
  INDEX `formularios_criador` (`criador` ASC),
  UNIQUE INDEX `forms_unico` (`nome` ASC, `pasta` ASC),
  CONSTRAINT `formularios_pasta`
    FOREIGN KEY (`pasta`)
    REFERENCES `pastas` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `formularios_criador`
    FOREIGN KEY (`criador`)
    REFERENCES `usuarios` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = 'Armazena todos os formulários de submissão';


-- -----------------------------------------------------
-- Table `downloads`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `downloads` (
  `anexo` INT NOT NULL COMMENT 'Referencia o anexo baixado',
  `usuario` INT NULL COMMENT 'Referencia o usuário que fez o donwload (NULL = não usuário)',
  `data` DATETIME NOT NULL COMMENT 'Indica a data do download',
  `email` VARCHAR(200) NULL COMMENT 'Em caso de não usuário, salva o email informado',
  `empresa` VARCHAR(200) NULL COMMENT 'Em caso de não usuário, salva a empresa informada',
  INDEX `downloads_anexo_idx` (`anexo` ASC),
  INDEX `downloads_usuario_idx` (`usuario` ASC),
  CONSTRAINT `downloads_anexo`
    FOREIGN KEY (`anexo`)
    REFERENCES `anexos` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `downloads_usuario`
    FOREIGN KEY (`usuario`)
    REFERENCES `usuarios` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = 'Armazena as estatísticas de downloas feitos';


-- -----------------------------------------------------
-- Table `logins`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `logins` (
  `usuario` INT NOT NULL,
  `sucesso` TINYINT(1) NOT NULL,
  `data` DATETIME NOT NULL,
  INDEX `logins_usuario_idx` (`usuario` ASC),
  CONSTRAINT `logins_usuario`
    FOREIGN KEY (`usuario`)
    REFERENCES `usuarios` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = 'Armazena as tentativas de login';


-- -----------------------------------------------------
-- Table `comentarios`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `comentarios` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT 'Identificador interno do comentário',
  `post` INT NOT NULL COMMENT 'Referencia o post no qual o comentário foi feito',
  `conteudo` TEXT NOT NULL COMMENT 'Conteúdo do comentário',
  `data` DATETIME NOT NULL COMMENT 'Data da criação do comentário',
  `modificacao` DATETIME NOT NULL COMMENT 'Data da última modificação do comentário',
  `criador` INT NOT NULL COMMENT 'Referencia o usuário que criou o comentário',
  PRIMARY KEY (`id`),
  INDEX `comentarios_post` (`post` ASC),
  INDEX `comentarios_criador` (`criador` ASC),
  CONSTRAINT `comentarios_post`
    FOREIGN KEY (`post`)
    REFERENCES `posts` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `comentarios_criador`
    FOREIGN KEY (`criador`)
    REFERENCES `usuarios` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = 'Comentários nos posts';

-- -----------------------------------------------------
-- Data for table `usuarios`
-- -----------------------------------------------------
START TRANSACTION;
INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `admin`, `ativo`, `usoMax`, `cookie`) VALUES (1, 'FEJESP', 'admin@email.com', '827ccb0eea8a706c4c34a16891f84e7b', 1, 1, 0, 'x');
INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `admin`, `ativo`, `usoMax`, `cookie`) VALUES (2, 'Usuário', 'user@email.com', '827ccb0eea8a706c4c34a16891f84e7b', 0, 1, 10240, 'y');

COMMIT;


-- -----------------------------------------------------
-- Data for table `pastas`
-- -----------------------------------------------------
START TRANSACTION;
INSERT INTO `pastas` (`id`, `nome`, `descricao`, `pai`, `visibilidade`, `criador`) VALUES (0, 'Diretório raiz', '', 0, 'publico', 0);

COMMIT;

-- Go back to original settings
SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
