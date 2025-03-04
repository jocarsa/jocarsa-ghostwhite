// charts.js

// Utility: Remove all children from a container.
function clearContainer(id) {
  var container = document.getElementById(id);
  while (container.firstChild) {
    container.removeChild(container.firstChild);
  }
  return container;
}

// Create (or get) a tooltip element.
function createTooltip() {
  var tooltip = document.getElementById("chart-tooltip");
  if (!tooltip) {
    tooltip = document.createElement("div");
    tooltip.id = "chart-tooltip";
    tooltip.style.position = "absolute";
    tooltip.style.background = "rgba(255,255,255,0.95)";
    tooltip.style.border = "1px solid #333";
    tooltip.style.padding = "5px 8px";
    tooltip.style.pointerEvents = "none";
    tooltip.style.display = "none";
    tooltip.style.fontSize = "12px";
    tooltip.style.color = "#333";
    tooltip.style.borderRadius = "3px";
    document.body.appendChild(tooltip);
  }
  return tooltip;
}

// Helper: Lighten a hex color by a percentage.
function lightenColor(hex, percent) {
  hex = hex.replace(/^\s*#|\s*$/g, '');
  if (hex.length === 3) {
    hex = hex.replace(/(.)/g, '$1$1');
  }
  var num = parseInt(hex, 16);
  var r = (num >> 16) + Math.round(255 * percent / 100);
  var g = ((num >> 8) & 0x00FF) + Math.round(255 * percent / 100);
  var b = (num & 0x0000FF) + Math.round(255 * percent / 100);
  r = (r < 255) ? r : 255;
  g = (g < 255) ? g : 255;
  b = (b < 255) ? b : 255;
  return "#" + ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1);
}

// Draw a Bar Chart with title, labels, values, and interactive hover.
function drawBarChart(containerId, data, chartTitle) {
  var container = clearContainer(containerId);
  var width = container.offsetWidth || 600;
  var height = 300;
  var padding = 40;
  var svgNS = "http://www.w3.org/2000/svg";
  var svg = document.createElementNS(svgNS, "svg");
  svg.setAttribute("width", width);
  svg.setAttribute("height", height);

  // Add title if provided.
  if (chartTitle) {
    var titleText = document.createElementNS(svgNS, "text");
    titleText.setAttribute("x", width / 2);
    titleText.setAttribute("y", 20);
    titleText.setAttribute("text-anchor", "middle");
    titleText.setAttribute("font-size", "16px");
    titleText.setAttribute("fill", "#333");
    titleText.textContent = chartTitle;
    svg.appendChild(titleText);
  }
  container.appendChild(svg);

  var maxVal = Math.max(...data.map(d => d.visits)) || 1;
  var barWidth = (width - 2 * padding) / data.length;
  var tooltip = createTooltip();

  data.forEach((d, i) => {
    var barHeight = (d.visits / maxVal) * (height - 2 * padding);
    var rect = document.createElementNS(svgNS, "rect");
    rect.setAttribute("x", padding + i * barWidth);
    rect.setAttribute("y", height - padding - barHeight);
    rect.setAttribute("width", barWidth - 5);
    rect.setAttribute("height", barHeight);
    rect.setAttribute("fill", "#333");
    rect.style.transition = "fill 0.2s";

    // Hover: change color and show tooltip.
    rect.addEventListener("mouseover", function (e) {
      rect.setAttribute("fill", "#555");
      tooltip.style.display = "block";
      tooltip.innerHTML = `<strong>${d.label}</strong>: ${d.visits}`;
    });
    rect.addEventListener("mousemove", function (e) {
      tooltip.style.left = e.pageX + 10 + "px";
      tooltip.style.top = e.pageY - 25 + "px";
    });
    rect.addEventListener("mouseout", function () {
      rect.setAttribute("fill", "#333");
      tooltip.style.display = "none";
    });
    svg.appendChild(rect);

    // Label below each bar.
    var text = document.createElementNS(svgNS, "text");
    text.setAttribute("x", padding + i * barWidth + (barWidth - 5) / 2);
    text.setAttribute("y", height - padding + 15);
    text.setAttribute("text-anchor", "middle");
    text.setAttribute("font-size", "10px");
    text.textContent = d.label;
    svg.appendChild(text);

    // Value above each bar.
    var valueText = document.createElementNS(svgNS, "text");
    valueText.setAttribute("x", padding + i * barWidth + (barWidth - 5) / 2);
    valueText.setAttribute("y", height - padding - barHeight - 5);
    valueText.setAttribute("text-anchor", "middle");
    valueText.setAttribute("font-size", "10px");
    valueText.setAttribute("fill", "#333");
    valueText.textContent = d.visits;
    svg.appendChild(valueText);
  });
}

// Draw a Line Chart with title, data point markers, labels, and interactive hover.
function drawLineChart(containerId, data, chartTitle) {
  var container = clearContainer(containerId);
  var width = container.offsetWidth || 600;
  var height = 300;
  var padding = 40;
  var svgNS = "http://www.w3.org/2000/svg";
  var svg = document.createElementNS(svgNS, "svg");
  svg.setAttribute("width", width);
  svg.setAttribute("height", height);

  if (chartTitle) {
    var titleText = document.createElementNS(svgNS, "text");
    titleText.setAttribute("x", width / 2);
    titleText.setAttribute("y", 20);
    titleText.setAttribute("text-anchor", "middle");
    titleText.setAttribute("font-size", "16px");
    titleText.setAttribute("fill", "#333");
    titleText.textContent = chartTitle;
    svg.appendChild(titleText);
  }
  container.appendChild(svg);

  var maxVal = Math.max(...data.map(d => d.visits)) || 1;
  var step = (width - 2 * padding) / (data.length - 1);
  var tooltip = createTooltip();
  var points = [];

  data.forEach((d, i) => {
    var x = padding + i * step;
    var y = height - padding - (d.visits / maxVal) * (height - 2 * padding);
    points.push(x + "," + y);
  });

  var polyline = document.createElementNS(svgNS, "polyline");
  polyline.setAttribute("points", points.join(" "));
  polyline.setAttribute("fill", "none");
  polyline.setAttribute("stroke", "#333");
  polyline.setAttribute("stroke-width", "2");
  svg.appendChild(polyline);

  data.forEach((d, i) => {
    var x = padding + i * step;
    var y = height - padding - (d.visits / maxVal) * (height - 2 * padding);
    var circle = document.createElementNS(svgNS, "circle");
    circle.setAttribute("cx", x);
    circle.setAttribute("cy", y);
    circle.setAttribute("r", 4);
    circle.setAttribute("fill", "#333");
    circle.style.transition = "fill 0.2s";

    circle.addEventListener("mouseover", function (e) {
      circle.setAttribute("fill", "#555");
      tooltip.style.display = "block";
      tooltip.innerHTML = `<strong>${d.label}</strong>: ${d.visits}`;
    });
    circle.addEventListener("mousemove", function (e) {
      tooltip.style.left = e.pageX + 10 + "px";
      tooltip.style.top = e.pageY - 25 + "px";
    });
    circle.addEventListener("mouseout", function () {
      circle.setAttribute("fill", "#333");
      tooltip.style.display = "none";
    });
    svg.appendChild(circle);

    // Label below the x-axis.
    var text = document.createElementNS(svgNS, "text");
    text.setAttribute("x", x);
    text.setAttribute("y", height - padding + 15);
    text.setAttribute("text-anchor", "middle");
    text.setAttribute("font-size", "10px");
    text.textContent = d.label;
    svg.appendChild(text);

    // Value above the point.
    var valueText = document.createElementNS(svgNS, "text");
    valueText.setAttribute("x", x);
    valueText.setAttribute("y", y - 10);
    valueText.setAttribute("text-anchor", "middle");
    valueText.setAttribute("font-size", "10px");
    valueText.setAttribute("fill", "#333");
    valueText.textContent = d.visits;
    svg.appendChild(valueText);
  });
}

// Draw a Pie Chart with title, segment labels, and interactive hover.
function drawPieChart(containerId, data, chartTitle) {
  var container = clearContainer(containerId);
  var width = container.offsetWidth || 300;
  var height = 300;
  var radius = Math.min(width, height) / 2 - 20;
  var cx = width / 2;
  var cy = height / 2;
  var svgNS = "http://www.w3.org/2000/svg";
  var svg = document.createElementNS(svgNS, "svg");
  svg.setAttribute("width", width);
  svg.setAttribute("height", height);

  if (chartTitle) {
    var titleText = document.createElementNS(svgNS, "text");
    titleText.setAttribute("x", width / 2);
    titleText.setAttribute("y", 20);
    titleText.setAttribute("text-anchor", "middle");
    titleText.setAttribute("font-size", "16px");
    titleText.setAttribute("fill", "#333");
    titleText.textContent = chartTitle;
    svg.appendChild(titleText);
  }
  container.appendChild(svg);

  var total = data.reduce((sum, d) => sum + Number(d.visits), 0) || 1;
  var angleStart = 0;
  var tooltip = createTooltip();
  var colors = ["#333", "#555", "#777", "#999", "#bbb", "#ddd", "#aaa"];

  data.forEach((d, i) => {
    var sliceAngle = (d.visits / total) * 2 * Math.PI;
    var angleEnd = angleStart + sliceAngle;
    var x1 = cx + radius * Math.cos(angleStart);
    var y1 = cy + radius * Math.sin(angleStart);
    var x2 = cx + radius * Math.cos(angleEnd);
    var y2 = cy + radius * Math.sin(angleEnd);
    var largeArcFlag = sliceAngle > Math.PI ? 1 : 0;
    var pathData = [
      "M", cx, cy,
      "L", x1, y1,
      "A", radius, radius, 0, largeArcFlag, 1, x2, y2,
      "Z"
    ].join(" ");

    var path = document.createElementNS(svgNS, "path");
    path.setAttribute("d", pathData);
    path.setAttribute("fill", colors[i % colors.length]);
    path.style.transition = "fill 0.2s";

    path.addEventListener("mouseover", function (e) {
      path.setAttribute("fill", lightenColor(colors[i % colors.length], 20));
      tooltip.style.display = "block";
      var percentage = ((d.visits / total) * 100).toFixed(1);
      tooltip.innerHTML = `<strong>${d.label}</strong>: ${d.visits} (${percentage}%)`;
    });
    path.addEventListener("mousemove", function (e) {
      tooltip.style.left = e.pageX + 10 + "px";
      tooltip.style.top = e.pageY - 25 + "px";
    });
    path.addEventListener("mouseout", function () {
      path.setAttribute("fill", colors[i % colors.length]);
      tooltip.style.display = "none";
    });
    svg.appendChild(path);

    // Compute midpoint for label.
    var midAngle = angleStart + sliceAngle / 2;
    var labelX = cx + (radius + 15) * Math.cos(midAngle);
    var labelY = cy + (radius + 15) * Math.sin(midAngle);
    var text = document.createElementNS(svgNS, "text");
    text.setAttribute("x", labelX);
    text.setAttribute("y", labelY);
    text.setAttribute("text-anchor", "middle");
    text.setAttribute("font-size", "10px");
    text.setAttribute("fill", "#333");
    text.textContent = d.label;
    svg.appendChild(text);

    angleStart += sliceAngle;
  });
}

// Redraw chart: accepts container id, data, chart type, and optional chart title.
function redrawChart(containerId, data, chartType, chartTitle) {
  chartTitle = chartTitle || "";
  if (chartType === "bar") {
    drawBarChart(containerId, data, chartTitle);
  } else if (chartType === "line") {
    drawLineChart(containerId, data, chartTitle);
  } else if (chartType === "pie") {
    drawPieChart(containerId, data, chartTitle);
  }
}
// Draw a Horizontal Bar Chart with title, labels, values, and interactive hover.
function drawHorizontalBarChart(containerId, data, chartTitle) {
  var container = clearContainer(containerId);
  var width = container.offsetWidth || 600;
  var height = 300;
  var padding = 40;
  var svgNS = "http://www.w3.org/2000/svg";
  var svg = document.createElementNS(svgNS, "svg");
  svg.setAttribute("width", width);
  svg.setAttribute("height", height);

  // Add title if provided.
  if (chartTitle) {
    var titleText = document.createElementNS(svgNS, "text");
    titleText.setAttribute("x", width / 2);
    titleText.setAttribute("y", 20);
    titleText.setAttribute("text-anchor", "middle");
    titleText.setAttribute("font-size", "16px");
    titleText.setAttribute("fill", "#333");
    titleText.textContent = chartTitle;
    svg.appendChild(titleText);
  }
  container.appendChild(svg);

  var maxVal = Math.max(...data.map(d => d.visits)) || 1;
  var barHeight = (height - 2 * padding) / data.length;
  var tooltip = createTooltip();

  data.forEach((d, i) => {
    var barWidth = (d.visits / maxVal) * (width - 2 * padding);
    var rect = document.createElementNS(svgNS, "rect");
    rect.setAttribute("x", padding);
    rect.setAttribute("y", padding + i * barHeight);
    rect.setAttribute("width", barWidth);
    rect.setAttribute("height", barHeight - 5);
    rect.setAttribute("fill", "#333");
    rect.style.transition = "fill 0.2s";

    // Hover: change color and show tooltip.
    rect.addEventListener("mouseover", function (e) {
      rect.setAttribute("fill", "#555");
      tooltip.style.display = "block";
      tooltip.innerHTML = `<strong>${d.label}</strong>: ${d.visits}`;
    });
    rect.addEventListener("mousemove", function (e) {
      tooltip.style.left = e.pageX + 10 + "px";
      tooltip.style.top = e.pageY - 25 + "px";
    });
    rect.addEventListener("mouseout", function () {
      rect.setAttribute("fill", "#333");
      tooltip.style.display = "none";
    });
    svg.appendChild(rect);

    // Label to the left of each bar.
    var text = document.createElementNS(svgNS, "text");
    text.setAttribute("x", padding - 5);
    text.setAttribute("y", padding + i * barHeight + (barHeight - 5) / 2);
    text.setAttribute("text-anchor", "end");
    text.setAttribute("font-size", "10px");
    text.textContent = d.label;
    svg.appendChild(text);

    // Value to the right of each bar.
    var valueText = document.createElementNS(svgNS, "text");
    valueText.setAttribute("x", padding + barWidth + 5);
    valueText.setAttribute("y", padding + i * barHeight + (barHeight - 5) / 2);
    valueText.setAttribute("text-anchor", "start");
    valueText.setAttribute("font-size", "10px");
    valueText.setAttribute("fill", "#333");
    valueText.textContent = d.visits;
    svg.appendChild(valueText);
  });
}
function drawTableChart(containerId, data, chartTitle) {
  var container = clearContainer(containerId);
  
  // Optionally add a title
  if (chartTitle) {
    var titleEl = document.createElement("h3");
    titleEl.textContent = chartTitle;
    container.appendChild(titleEl);
  }
  
  // Create table
  var table = document.createElement("table");
  table.style.width = "100%";
  table.style.borderCollapse = "collapse";
  
  // Create header row
  var thead = document.createElement("thead");
  var headerRow = document.createElement("tr");
  
  var thLabel = document.createElement("th");
  thLabel.textContent = "Label";
  thLabel.style.border = "1px solid #ccc";
  thLabel.style.padding = "5px";
  
  var thVisits = document.createElement("th");
  thVisits.textContent = "Visits";
  thVisits.style.border = "1px solid #ccc";
  thVisits.style.padding = "5px";
  
  headerRow.appendChild(thLabel);
  headerRow.appendChild(thVisits);
  thead.appendChild(headerRow);
  table.appendChild(thead);
  
  // Create table body
  var tbody = document.createElement("tbody");
  data.forEach(function(d) {
    var row = document.createElement("tr");
    
    var tdLabel = document.createElement("td");
    tdLabel.textContent = d.label;
    tdLabel.style.border = "1px solid #ccc";
    tdLabel.style.padding = "5px";
    
    var tdVisits = document.createElement("td");
    tdVisits.textContent = d.visits;
    tdVisits.style.border = "1px solid #ccc";
    tdVisits.style.padding = "5px";
    
    row.appendChild(tdLabel);
    row.appendChild(tdVisits);
    tbody.appendChild(row);
  });
  table.appendChild(tbody);
  container.appendChild(table);
}
// Update the redrawChart function to include the new chart type.
function redrawChart(containerId, data, chartType, chartTitle) {
  chartTitle = chartTitle || "";
  if (chartType === "bar") {
    drawBarChart(containerId, data, chartTitle);
  } else if (chartType === "line") {
    drawLineChart(containerId, data, chartTitle);
  } else if (chartType === "pie") {
    drawPieChart(containerId, data, chartTitle);
  } else if (chartType === "horizontal") {
    drawHorizontalBarChart(containerId, data, chartTitle);
  } else if (chartType === "table") {
    drawTableChart(containerId, data, chartTitle);
  }
}

