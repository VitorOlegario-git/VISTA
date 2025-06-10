
# 📊 VISTA – Visão Integrada de Sistemas Técnicos e Análise

Sistema completo de indicadores técnicos para gerenciamento da assistência técnica. O VISTA centraliza dados operacionais, gera KPIs visuais em tempo real e permite acompanhamento detalhado de cada setor envolvido no processo: Recebimento, Análise, Reparo, Qualidade e Expedição.

---

## 🚀 Funcionalidades

- 📅 Filtro por período, CNPJ, nota fiscal, operação ou colaborador
- 📈 Dashboards por setor com KPIs interativos
- 📊 Gráficos dinâmicos com Chart.js
- 📤 Exportação de relatórios em PDF e Excel
- 🧠 IA embarcada para previsão de tendências (em desenvolvimento)
- ⚙️ Triggers automáticas em MySQL para controle de status e histórico
- 🧾 Registro completo de apontamentos e orçamentos técnicos
- 🔒 Sistema com login e controle de sessão por tempo de inatividade

---

## 🧰 Tecnologias utilizadas

![HTML5](https://img.shields.io/badge/-HTML5-E34F26?style=flat&logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/-CSS3-1572B6?style=flat&logo=css3&logoColor=white)
![JavaScript](https://img.shields.io/badge/-JavaScript-F7DF1E?style=flat&logo=javascript&logoColor=black)
![PHP](https://img.shields.io/badge/-PHP-777BB4?style=flat&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/-MySQL-4479A1?style=flat&logo=mysql&logoColor=white)
![Chart.js](https://img.shields.io/badge/-Chart.js-F5788D?style=flat&logo=chartdotjs&logoColor=white)
![Git](https://img.shields.io/badge/-Git-F05032?style=flat&logo=git&logoColor=white)


---

## 📂 Estrutura do Projeto

\`\`\`
VISTA/
├── FrontEnd/
│   ├── html/            # Interfaces do sistema
│   ├── js/              # Scripts interativos
│   └── css/             # Estilos
├── BackEnd/
│   ├── Reparo/          # Lógica PHP setor Reparo
│   ├── Analise/         # Lógica PHP setor Análise
│   └── Qualidade/       # Lógica PHP setor Qualidade
├── DashBoard/           # Tela principal de KPIs
├── kpi_2_0.sql          # Estrutura do banco de dados MySQL
└── index.php            # Tela de entrada
\`\`\`

---

## 🧪 Como usar

1. Clone o repositório:

\`\`\`bash
git clone https://github.com/VitorOlegario-git/VISTA.git
\`\`\`

2. Importe o banco de dados \`kpi_2_0.sql\` no seu MySQL

3. Configure o caminho do seu servidor no \`/BackEnd/conexao.php\`

4. Rode o sistema em um servidor local (XAMPP ou LAMP)

---

## 📌 Status do Projeto

⚙️ Melhorias contínuas em visualização, usabilidade e IA  
🔒 Foco em segurança e controle de acesso

---

## 👨‍💻 Autor

Desenvolvido por **Vitor Olegário**  
🎓 Estudante de Engenharia de Software | 🛠 Técnico líder na Suntech do Brasil

📧 olegario.vitor43@gmail.com  
🔗 [LinkedIn](https://www.linkedin.com/in/vitor-olegario)  
📂 [Portfólio GitHub](https://github.com/VitorOlegario-git)

---

⭐ Se este projeto te ajudou, deixe uma estrela no repositório!
