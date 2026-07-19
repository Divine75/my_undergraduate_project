// assets/js/genealogy.js - Interactive SVG family tree drawer

document.addEventListener("DOMContentLoaded", function() {
    const svg = document.getElementById("genealogySvg");
    if (!svg) return;

    // Create a map of nodes by ID
    const nodesMap = {};
    GENEALOGY_NODES.forEach(n => {
        nodesMap[n.id] = n;
    });

    const root = nodesMap[ROOT_MEMBER_ID];
    if (!root) return;

    // 1. Resolve Ancestors & Spouses
    const father = root.father_id ? nodesMap[root.father_id] : null;
    const mother = root.mother_id ? nodesMap[root.mother_id] : null;
    const spouse = root.spouse_id ? nodesMap[root.spouse_id] : null;

    const patGf = (father && father.father_id) ? nodesMap[father.father_id] : null;
    const patGm = (father && father.mother_id) ? nodesMap[father.mother_id] : null;
    const matGf = (mother && mother.father_id) ? nodesMap[mother.father_id] : null;
    const matGm = (mother && mother.mother_id) ? nodesMap[mother.mother_id] : null;

    // Fetch children
    const children = GENEALOGY_NODES.filter(n => n.father_id === ROOT_MEMBER_ID || n.mother_id === ROOT_MEMBER_ID);

    // 2. Coordinates
    // Level 1: Grandparents (y = 50)
    const y1 = 50;
    const xPatGf = 100, xPatGm = 220;
    const xMatGf = 500, xMatGm = 620;

    // Level 2: Parents (y = 150)
    const y2 = 150;
    const xFather = 160;
    const xMother = 560;

    // Level 3: Root & Spouse (y = 260)
    const y3 = 260;
    const xRoot = 300;
    const xSpouse = 460;

    // Level 4: Children (y = 370)
    const y4 = 370;

    // 3. Draw Lines / Connectors
    
    // Connect Paternal Grandparents to Father
    if (patGf || patGm) {
        drawLine(xPatGf, y1, xPatGm, y1, (!patGf || !patGm));
        const midPatX = (xPatGf + xPatGm) / 2;
        drawLine(midPatX, y1, midPatX, y2 - 27, (!father));
    }
    
    // Connect Maternal Grandparents to Mother
    if (matGf || matGm) {
        drawLine(xMatGf, y1, xMatGm, y1, (!matGf || !matGm));
        const midMatX = (xMatGf + xMatGm) / 2;
        drawLine(midMatX, y1, midMatX, y2 - 27, (!mother));
    }

    // Connect Parents to Root
    if (father || mother) {
        // Draw vertical lines down from parents
        drawLine(xFather, y2, xFather, y2 + 40, !father);
        drawLine(xMother, y2, xMother, y2 + 40, !mother);
        // Horizontal connection line
        drawLine(xFather, y2 + 40, xMother, y2 + 40, (!father || !mother));
        // Vertical line down to Root level
        const midParentsX = (xFather + xMother) / 2;
        drawLine(midParentsX, y2 + 40, midParentsX, y3 - 27, false);
        // Connect to Root node
        drawLine(midParentsX, y3 - 27, xRoot, y3 - 27, false);
        drawLine(xRoot, y3 - 27, xRoot, y3 - 27, false);
    }

    // Connect Root & Spouse
    if (spouse) {
        drawLine(xRoot, y3, xSpouse, y3, false);
    }

    // Connect Root & Spouse to Children
    if (children.length > 0) {
        const midRootSpouseX = spouse ? (xRoot + xSpouse) / 2 : xRoot;
        // Line down to children branch
        drawLine(midRootSpouseX, y3, midRootSpouseX, y4 - 30);
        
        // Distribute children
        const childrenCount = children.length;
        const xCoords = [];
        children.forEach((c, idx) => {
            const xVal = midRootSpouseX + (idx - (childrenCount - 1)/2) * 150;
            xCoords.push(xVal);
            c.x = xVal;
            c.y = y4;
        });

        // Draw children branch connector line
        if (childrenCount > 1) {
            drawLine(xCoords[0], y4 - 30, xCoords[childrenCount - 1], y4 - 30);
        }

        // Draw line down to each child
        children.forEach(c => {
            drawLine(c.x, y4 - 30, c.x, c.y - 27);
        });
    }

    // 4. Draw Nodes
    
    // Draw Level 1: Grandparents
    drawNode(xPatGf, y1, patGf, "No Record", "Paternal Grandfather");
    drawNode(xPatGm, y1, patGm, "No Record", "Paternal Grandmother");
    drawNode(xMatGf, y1, matGf, "No Record", "Maternal Grandfather");
    drawNode(xMatGm, y1, matGm, "No Record", "Maternal Grandmother");

    // Draw Level 2: Parents
    drawNode(xFather, y2, father, "No Record", "Father");
    drawNode(xMother, y2, mother, "No Record", "Mother");

    // Draw Level 3: Root & Spouse
    drawNode(xRoot, y3, root, "", "");
    if (spouse || root.spouse_id) {
        drawNode(xSpouse, y3, spouse, "No Record", "Spouse");
    }

    // Draw Level 4: Children
    children.forEach(c => {
        drawNode(c.x, c.y, c, "", "Child");
    });


    // --- DRAWING HELPERS ---

    function drawLine(x1, y1, x2, y2, isDashed = false) {
        const line = document.createElementNS("http://www.w3.org/2000/svg", "line");
        line.setAttribute("x1", x1);
        line.setAttribute("y1", y1);
        line.setAttribute("x2", x2);
        line.setAttribute("y2", y2);
        line.setAttribute("stroke", "#D4AF37");
        line.setAttribute("stroke-width", "2");
        if (isDashed) {
            line.setAttribute("stroke-dasharray", "4,4");
            line.setAttribute("stroke", "#b8c3c0");
        }
        svg.appendChild(line);
    }

    function drawNode(x, y, member, placeholderText, genderLabel) {
        const width = 120;
        const height = 54;
        const rectX = x - width / 2;
        const rectY = y - height / 2;
        
        let fill = "#0F3057";
        let stroke = "#D4AF37";
        let textColor = "#ffffff";
        let titleText = "";
        let subText = "";
        
        if (member) {
            titleText = member.name;
            subText = member.title ? member.title : (member.gender === 'Male' ? 'Male' : 'Female');
            
            // Highlight root member
            if (member.id === ROOT_MEMBER_ID) {
                fill = "#D4AF37";
                stroke = "#0F3057";
                textColor = "#0F3057";
            }
        } else {
            fill = "#f2f4f3";
            stroke = "#b8c3c0";
            textColor = "#8c9b97";
            titleText = placeholderText;
            subText = genderLabel;
        }

        // Draw Rectangle
        const rect = document.createElementNS("http://www.w3.org/2000/svg", "rect");
        rect.setAttribute("x", rectX);
        rect.setAttribute("y", rectY);
        rect.setAttribute("width", width);
        rect.setAttribute("height", height);
        rect.setAttribute("rx", "8");
        rect.setAttribute("fill", fill);
        rect.setAttribute("stroke", stroke);
        rect.setAttribute("stroke-width", member ? "2.5" : "1.5");
        if (!member) {
            rect.setAttribute("stroke-dasharray", "4,4");
        }
        rect.setAttribute("style", "filter: drop-shadow(0px 3px 5px rgba(0,0,0,0.15));");
        svg.appendChild(rect);

        // Draw Name text
        const textName = document.createElementNS("http://www.w3.org/2000/svg", "text");
        textName.setAttribute("x", x);
        textName.setAttribute("y", y - 4);
        textName.setAttribute("font-size", "10px");
        textName.setAttribute("font-family", "'Outfit', sans-serif");
        textName.setAttribute("font-weight", "600");
        textName.setAttribute("text-anchor", "middle");
        textName.setAttribute("fill", textColor);
        
        const displayName = titleText.length > 18 ? titleText.substring(0, 16) + ".." : titleText;
        textName.textContent = displayName;
        svg.appendChild(textName);

        // Draw Role / Gender subtitle text
        const textSub = document.createElementNS("http://www.w3.org/2000/svg", "text");
        textSub.setAttribute("x", x);
        textSub.setAttribute("y", y + 12);
        textSub.setAttribute("font-size", "8px");
        textSub.setAttribute("font-family", "'Outfit', sans-serif");
        textSub.setAttribute("text-anchor", "middle");
        textSub.setAttribute("fill", member && member.id === ROOT_MEMBER_ID ? "rgba(15,48,87,0.85)" : "rgba(255,255,255,0.65)");
        if (!member) {
            textSub.setAttribute("fill", "#a3b2ae");
        }
        
        const displaySub = subText.length > 20 ? subText.substring(0, 18) + ".." : subText;
        textSub.textContent = displaySub;
        svg.appendChild(textSub);

        // Add interactive pointer & links
        if (member) {
            rect.setAttribute("style", "cursor: pointer; filter: drop-shadow(0px 4px 6px rgba(0,0,0,0.15));");
            rect.addEventListener("click", function() {
                window.location.href = "member-details.php?id=" + member.id;
            });
            textName.setAttribute("style", "cursor: pointer;");
            textName.addEventListener("click", function() {
                window.location.href = "member-details.php?id=" + member.id;
            });
        }
    }
});
