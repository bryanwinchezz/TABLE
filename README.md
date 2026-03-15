# 🎲 TABLE - Plataforma Digital para RPG de Mesa

![Status do Projeto](https://img.shields.io/badge/Status-Em_Desenvolvimento-8b5cf6?style=for-the-badge)
![Linguagem](https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white)
![Estilização](https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white)
![Lógica](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)

A **TABLE** é um sistema web desenvolvido para reunir, em uma única plataforma, as ferramentas essenciais para jogadores e mestres de RPG. Nosso objetivo é facilitar a criação, organização e condução de campanhas, substituindo o papel e caneta por uma experiência digital imersiva, fluida e visualmente atraente.

## ✨ Funcionalidades Principais

* **🧙‍♂️ Criação de Personagens (Fichas Digitais):** * Formulário interativo com abas separadas para Descrição, Origem, Atributos e Classe.
  * Sistema de distribuição de pontos de atributos inteligente (limite máximo de pontos e valores base) com validação em tempo real.
  * Seleção visual de cards de classes e origens.
* **⚙️ Criação de Sistemas Próprios:**
  * Painel de construção de sistemas para Mestres.
  * Adição de status, atributos e barras de defesa personalizadas.
  * Criação dinâmica de componentes (Perícias, Monstros, Poderes, Equipamentos) via Modais.
* **📖 Guia para Iniciantes (Como Jogar):**
  * Área educativa que explica os conceitos básicos do RPG (com direito a referências de cultura pop).
  * Trilhas separadas ensinando as responsabilidades de como ser um bom **Jogador** e como ser um bom **Mestre**.
* **🎨 Design UI/UX Imersivo:**
  * Tema escuro (Dark Mode) padronizado com tons de roxo (`#311C61` e `#C193FD`).
  * Animações suaves em menus, sanfonas (accordions) e botões.

## 🚀 Tecnologias Utilizadas

Este projeto foi construído puramente com tecnologias front-end fundamentais, sem a dependência de frameworks pesados, garantindo alta performance e controle total sobre a manipulação do DOM.

* **HTML5:** Semântica estruturada.
* **CSS3:** Flexbox, CSS Grid, Variáveis Globais (`:root`), Animações e Responsividade.
* **JavaScript (ES6+):** Vanilla JS para controle de abas, validação de formulários, matemática de atributos e simulações de carregamento (Assincronicidade com `setTimeout`).
* **Font Awesome:** Biblioteca de ícones.
* **Google Fonts:** Tipografia principal `Montserrat`.

## 📂 Estrutura do Projeto

```text
/
├── css/
│   ├── criar-personagem.css
│   ├── criar-sistema.css
│   ├── index.css
│   ├── nav-footer.css
│   └── pgs-como.css
├── js/
│   ├── criar-personagem.js
│   ├── criar-sistema.js
│   ├── nav-global.js
│   └── script.js
├── img/
│   └── (Imagens, logos e assets do site)
└── html/ (ou raiz)
    ├── index.html
    ├── cm-jogar.html
    ├── cm-jogador.html
    ├── cm-mestre.html
    ├── criar-personagem.html
    └── criar-sistema.html
💻 Como rodar o projeto localmente
Como o projeto é estático (Front-end puro), não é necessário instalar dependências ou usar terminais complexos.

Faça o clone deste repositório:

Bash

git clone [https://github.com/bryanwinchezz/table](https://github.com/bryanwinchezz/table)
Acesse a pasta do projeto:

Bash

cd table-rpg
Abra o arquivo index.html diretamente no seu navegador, ou utilize a extensão Live Server do VS Code para visualizar as alterações em tempo real.

📸 Telas do Projeto (Preview)
Dica para o Desenvolvedor: Adicione aqui screenshots do seu projeto funcionando. Substitua os links abaixo pelas imagens reais depois que subir no GitHub!

<div align="center">
<img src="URL_DA_SUA_IMAGEM_DA_HOME" alt="Página Inicial" width="400">
<img src="URL_DA_SUA_IMAGEM_DE_CRIAR_PERSONAGEM" alt="Criação de Personagem" width="400">
</div>

<p align="center">Desenvolvido com dedicação por <b>SYNERA</b> 🎲</p>
