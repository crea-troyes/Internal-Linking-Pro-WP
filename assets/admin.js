jQuery(function ($) {
  const i18n = (window.CMA && CMA.i18n) || {};

  function setStatus(txt, isError) {
    $('#cma-status')
      .text(txt || '')
      .toggleClass('is-error', !!isError);
  }

  $('#cma-run-scan').on('click', function () {
    setStatus(i18n.scanRunning || 'Scan in progress... Do not close this page.', false);

    $.post(CMA.ajaxUrl, {
      action: 'cma_run_scan',
      nonce: CMA.nonce
    })
      .done(function (res) {
        if (res && res.success) {
          setStatus(res.data.message + ' (' + res.data.counts.items + ' ' + (i18n.contentItems || 'content items') + ')', false);
          window.location.reload();
        } else {
          setStatus((res && res.data && res.data.message) ? res.data.message : (i18n.unknownError || 'Unknown error.'), true);
        }
      })
      .fail(function () {
        setStatus(i18n.ajaxError || 'AJAX error. A server timeout may have occurred.', true);
      });
  });

  $('#cma-clear-scan').on('click', function () {
    if (!confirm(i18n.confirmClearCache || 'Delete the cached analysis?')) return;

    setStatus(i18n.clearingCache || 'Clearing cache...', false);

    $.post(CMA.ajaxUrl, {
      action: 'cma_clear_scan',
      nonce: CMA.nonce
    })
      .done(function (res) {
        if (res && res.success) {
          setStatus(res.data.message, false);
          window.location.reload();
        } else {
          setStatus((res && res.data && res.data.message) ? res.data.message : (i18n.unknownError || 'Unknown error.'), true);
        }
      })
      .fail(function () {
        setStatus(i18n.ajaxError || 'AJAX error.', true);
      });
  });
});

document.addEventListener('DOMContentLoaded', function () {
  const i18n = (window.CMA && CMA.i18n) || {};
  document.querySelectorAll('.cma-conflicts-toggle').forEach(function (button) {
    button.addEventListener('click', function () {
      const targetId = button.getAttribute('aria-controls');
      const row = targetId ? document.getElementById(targetId) : null;

      if (!row) return;

      const isOpen = row.classList.toggle('is-open');
      const label = button.querySelector('.cma-conflicts-toggle-label');
      button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      button.classList.toggle('is-open', isOpen);
      if (label) {
        label.textContent = isOpen ? (i18n.hide || 'Hide') : (i18n.details || 'Details');
      }
    });
  });
});

jQuery(document).ready(function($) {
    $(document).on('click', '#tableau tbody:first-child th', function() {
        var $th = $(this);
        var $table = $('#tableau');
        
        var $tbodyData = $table.find('tbody').eq(1); 
        
        if ($tbodyData.length === 0) $tbodyData = $table.find('tbody').first();

        var index = $th.index();
        var isAsc = $th.attr('data-sort-dir') !== 'asc';

        var rows = $tbodyData.find('tr').filter(function() {
            return $(this).find('td').length > 0;
        }).get();

        rows.sort(function(a, b) {
            var valA = $(a).find('td').eq(index).text().trim();
            var valB = $(b).find('td').eq(index).text().trim();

            var numA = parseFloat(valA.replace(/[^\d.-]/g, ''));
            var numB = parseFloat(valB.replace(/[^\d.-]/g, ''));

            if (!isNaN(numA) && !isNaN(numB)) {
                return isAsc ? numA - numB : numB - numA;
            }
            return isAsc ? valA.localeCompare(valB, 'fr') : valB.localeCompare(valA, 'fr');
        });

        $tbodyData.empty();

        var fragment = document.createDocumentFragment();
        $.each(rows, function(i, row) {
            fragment.appendChild(row);
        });
        $tbodyData.append(fragment);

        $table.find('th').removeAttr('data-sort-dir');
        $th.attr('data-sort-dir', isAsc ? 'asc' : 'desc');
    });

    $('#tableau tbody:first-child th').css('cursor', 'pointer');
});



(function () {
  const i18n = (window.CMA && CMA.i18n) || {};
  const graphContainer = document.getElementById('cma-graph');
  const wrapper = document.getElementById('cma-graph-wrapper');

  if (!graphContainer || typeof vis === 'undefined') return;

  function escapeHtml(value) {
    const element = document.createElement('div');
    element.textContent = String(value || '');
    return element.innerHTML;
  }

  function safeExternalUrl(value) {
    try {
      const url = new URL(String(value || ''), window.location.origin);
      return /^https?:$/.test(url.protocol) ? url.href : '';
    } catch (error) {
      return '';
    }
  }

  const raw = graphContainer.dataset.graph;
  if (!raw) return;

  let graphData;

  try {
    graphData = JSON.parse(raw);
  } catch (error) {
    console.error('Invalid graph JSON:', error);
    return;
  }

  if (!graphData.nodes || !graphData.edges) return;

  graphContainer.style.position = 'relative';

  const loader = document.createElement('div');
  loader.id = 'cma-loader';
  const spinner = document.createElement('div');
  spinner.className = 'cma-spinner';
  const loaderLabel = document.createElement('p');
  loaderLabel.textContent = i18n.mapLoading || 'Calculating map...';
  loader.append(spinner, loaderLabel);
  loader.style.cssText = `
    position:absolute;
    inset:0 360px 0 0;
    display:flex;
    align-items:center;
    justify-content:center;
    flex-direction:column;
    text-align:center;
    z-index:10;
    pointer-events:none;
  `;

  (wrapper || graphContainer).appendChild(loader);

  const nodesIndex = {};
  const connectedNodes = {};
  const incomingCount = {};
  const outgoingCount = {};
  const allEdgesIds = [];

  graphData.nodes.forEach(function (node) {
    nodesIndex[node.id] = node;
    connectedNodes[node.id] = new Set();
    incomingCount[node.id] = 0;
    outgoingCount[node.id] = 0;
  });

  graphData.edges.forEach(function (edge) {
    if (connectedNodes[edge.from]) connectedNodes[edge.from].add(edge.to);
    if (connectedNodes[edge.to]) connectedNodes[edge.to].add(edge.from);

    if (incomingCount[edge.to] !== undefined) incomingCount[edge.to]++;
    if (outgoingCount[edge.from] !== undefined) outgoingCount[edge.from]++;
  });

  const clusterColors = [
    {
      bg: 'rgba(37, 99, 235, 0.10)',
      border: 'rgba(37, 99, 235, 0.25)',
      text: '#1d4ed8'
    },
    {
      bg: 'rgba(22, 163, 74, 0.10)',
      border: 'rgba(22, 163, 74, 0.25)',
      text: '#15803d'
    },
    {
      bg: 'rgba(124, 58, 237, 0.10)',
      border: 'rgba(124, 58, 237, 0.25)',
      text: '#6d28d9'
    },
    {
      bg: 'rgba(245, 158, 11, 0.12)',
      border: 'rgba(245, 158, 11, 0.30)',
      text: '#b45309'
    },
    {
      bg: 'rgba(236, 72, 153, 0.10)',
      border: 'rgba(236, 72, 153, 0.25)',
      text: '#be185d'
    },
    {
      bg: 'rgba(14, 165, 233, 0.10)',
      border: 'rgba(14, 165, 233, 0.25)',
      text: '#0369a1'
    }
  ];

  const PILLAR_MIN_INCOMING = Math.max(
    parseInt(graphContainer.dataset.threshold || 5,10),
    Math.round(graphData.nodes.length * 0.01)
  );
  
  const clusters = {};
  const nodeClusterMap = {};

  const potentialPillars = graphData.nodes
    .filter(function (node) {
      return incomingCount[node.id] >= PILLAR_MIN_INCOMING;
    })
    .sort(function (a, b) {
      return incomingCount[b.id] - incomingCount[a.id];
    })
    .slice(0, 12);

  potentialPillars.forEach(function (pillar, index) {
    const sources = graphData.edges
      .filter(function (edge) {
        return edge.to == pillar.id;
      })
      .map(function (edge) {
        return edge.from;
      });

    const uniqueSources = Array.from(new Set(sources));

    const totalLinks = uniqueSources.reduce(function (total, id) {
      return total + (incomingCount[id] || 0) + (outgoingCount[id] || 0);
    }, 0);

    const density = uniqueSources.length
      ? Math.round(totalLinks / uniqueSources.length)
      : 0;

    const score = Math.min(100, Math.round(
      incomingCount[pillar.id] * 8 +
      uniqueSources.length * 4 +
      density * 2
    ));

    clusters[pillar.id] = {
      id: pillar.id,
      pillarId: pillar.id,
      label: pillar.label || pillar.title || 'Cluster',
      index: index,
      color: clusterColors[index % clusterColors.length],
      nodes: new Set([pillar.id]),
      score: score
    };

    nodeClusterMap[pillar.id] = pillar.id;

    uniqueSources.forEach(function (sourceId) {
      if (!nodeClusterMap[sourceId]) {
        nodeClusterMap[sourceId] = pillar.id;
        clusters[pillar.id].nodes.add(sourceId);
      }
    });
  });

  graphData.nodes.forEach(function (node) {
    if (!nodeClusterMap[node.id]) {
      nodeClusterMap[node.id] = null;
    }
  });

  const clusterIds = Object.keys(clusters);
  const clusterCount = Math.max(clusterIds.length, 1);
  const orbitRadius = Math.max(520, clusterCount * 95);

  clusterIds.forEach(function (clusterId, index) {
    const angle = (Math.PI * 2 * index) / clusterCount;

    clusters[clusterId].center = {
      x: Math.cos(angle) * orbitRadius,
      y: Math.sin(angle) * orbitRadius
    };
  });

  function getInitialPosition(node) {
    const clusterId = nodeClusterMap[node.id];

    if (!clusterId || !clusters[clusterId]) {
      return {
        x: (Math.random() - 0.5) * 1200,
        y: (Math.random() - 0.5) * 1200
      };
    }

    const cluster = clusters[clusterId];
    const isPillar = node.id == cluster.pillarId;

    if (isPillar) {
      return {
        x: cluster.center.x,
        y: cluster.center.y
      };
    }

    const clusterNodes = Array.from(cluster.nodes).filter(function (id) {
      return id != cluster.pillarId;
    });

    const index = Math.max(0, clusterNodes.indexOf(node.id));
    const total = Math.max(clusterNodes.length, 1);
    const angle = (Math.PI * 2 * index) / total;
    const radius = 130 + Math.min(total, 12) * 8;

    return {
      x: cluster.center.x + Math.cos(angle) * radius,
      y: cluster.center.y + Math.sin(angle) * radius
    };
  }

  function isPillar(node) {
    return clusters[node.id] !== undefined;
  }

  function getNodeColor(node) {
    if (node.is_isolated) {
      return {
        background: '#fee2e2',
        border: '#dc2626',
        highlight: {
          background: '#fecaca',
          border: '#991b1b'
        },
        hover: {
          background: '#fecaca',
          border: '#991b1b'
        }
      };
    }

    if (isPillar(node)) {
      return {
        background: '#facc15',
        border: '#b45309',
        highlight: {
          background: '#fde68a',
          border: '#92400e'
        },
        hover: {
          background: '#fde68a',
          border: '#92400e'
        }
      };
    }

    const clusterId = nodeClusterMap[node.id];

    if (clusterId && clusters[clusterId]) {
      return {
        background: '#ffffff',
        border: clusters[clusterId].color.text,
        highlight: {
          background: '#f8fafc',
          border: clusters[clusterId].color.text
        },
        hover: {
          background: '#f8fafc',
          border: clusters[clusterId].color.text
        }
      };
    }

    if (node.type === 'page') {
      return {
        background: '#dcfce7',
        border: '#16a34a',
        highlight: {
          background: '#bbf7d0',
          border: '#15803d'
        },
        hover: {
          background: '#bbf7d0',
          border: '#15803d'
        }
      };
    }

    return {
      background: '#dbeafe',
      border: '#2563eb',
      highlight: {
        background: '#bfdbfe',
        border: '#1d4ed8'
      },
      hover: {
        background: '#bfdbfe',
        border: '#1d4ed8'
      }
    };
  }

  function getNodeSize(node) {
    const incoming = incomingCount[node.id] || 0;

    if (node.is_isolated) return 14;
    if (isPillar(node)) return 32;

    if (incoming >= 20) return 30;
    if (incoming >= 15) return 24;
    if (incoming >= 10) return 20;
    if (incoming >= 5) return 18;
    if (incoming >= 2) return 16;

    return 13;
  }

  function getNodeBorderWidth(node) {
    const incoming = incomingCount[node.id] || 0;

    if (node.is_isolated) return 4;
    if (isPillar(node)) return 5;
    if (incoming >= 10) return 4;
    if (incoming >= 5) return 3;

    return 2;
  }

  const nodes = new vis.DataSet(graphData.nodes.map(function (node) {
    const position = getInitialPosition(node);
    const clusterId = nodeClusterMap[node.id];
    const cluster = clusterId ? clusters[clusterId] : null;

    return {
      id: node.id,
      label: node.label || node.title || ('ID ' + node.id),
      title: `
        <strong>${escapeHtml(node.label || node.title || (i18n.untitled || 'Untitled'))}</strong><br>
        ${escapeHtml(i18n.type || 'Type')}: ${escapeHtml(node.type === 'page' ? (i18n.page || 'Page') : (i18n.post || 'Post'))}<br>
        ${escapeHtml(i18n.incomingLinks || 'Incoming links')}: ${incomingCount[node.id] || 0}<br>
        ${escapeHtml(i18n.outgoingLinks || 'Outgoing links')}: ${outgoingCount[node.id] || 0}<br>
        ${isPillar(node) ? '⭐ ' + escapeHtml(i18n.pillarPage || 'Pillar page') + '<br>' : ''}
        ${cluster ? escapeHtml(i18n.cluster || 'Cluster') + ': ' + escapeHtml(cluster.label) + '<br>' : ''}
        ${node.is_isolated ? '⚠️ ' + escapeHtml(i18n.isolatedContent || 'Isolated content') + '<br>' : ''}
      `,
      color: getNodeColor(node),
      shape: isPillar(node) ? 'circle' : 'dot',
      size: getNodeSize(node),
      borderWidth: getNodeBorderWidth(node),
      x: position.x,
      y: position.y,
      fixed: false,
      mass: isPillar(node) ? 8 : 2,
      font: {
        size: isPillar(node) ? 16 : 13,
        color: '#1f2937',
        face: 'Arial',
        bold: isPillar(node),
        strokeWidth: 3,
        strokeColor: '#ffffff'
      }
    };
  }));

  const edges = new vis.DataSet(graphData.edges.map(function (edge, index) {
    const id = edge.id || ('edge-' + index);
    allEdgesIds.push(id);

    const sameCluster =
      nodeClusterMap[edge.from] &&
      nodeClusterMap[edge.from] === nodeClusterMap[edge.to];

    return {
      id: id,
      from: edge.from,
      to: edge.to,
      arrows: {
        to: {
          enabled: true,
          scaleFactor: 0.55
        }
      },
      width: sameCluster ? 1.8 : 1,
      color: {
        color: sameCluster ? '#64748b' : '#cbd5e1',
        highlight: '#2563eb',
        hover: '#2563eb',
        opacity: sameCluster ? 0.55 : 0.25
      },
      smooth: {
        enabled: true,
        type: 'dynamic',
        roundness: 0.35
      },
      title: edge.anchor ? escapeHtml(i18n.anchor || 'Anchor') + ': ' + escapeHtml(edge.anchor) : ''
    };
  }));

  const options = {
    autoResize: true,

    layout: {
      improvedLayout: false
    },

    physics: {
      enabled: true,
      solver: 'barnesHut',
      barnesHut: {
        gravitationalConstant: -8500,
        centralGravity: 0.08,
        springLength: 210,
        springConstant: 0.035,
        damping: 0.48,
        avoidOverlap: 1
      },
      stabilization: {
        enabled: true,
        iterations: 380,
        updateInterval: 25
      }
    },

    interaction: {
      hover: true,
      tooltipDelay: 120,
      hideEdgesOnDrag: true,
      navigationButtons: true,
      keyboard: true,
      multiselect: false
    },

    nodes: {
      shadow: {
        enabled: true,
        color: 'rgba(15,23,42,.18)',
        size: 8,
        x: 0,
        y: 4
      }
    },

    edges: {
      selectionWidth: 2,
      hoverWidth: 2
    }
  };

  const network = new vis.Network(graphContainer, { nodes, edges }, options);

  network.stabilize(380);

  network.on('beforeDrawing', function (ctx) {
    clusterIds.forEach(function (clusterId) {
      const cluster = clusters[clusterId];
      const ids = Array.from(cluster.nodes);

      const positions = network.getPositions(ids);
      const points = Object.values(positions);

      if (!points.length) return;

      let minX = Infinity;
      let minY = Infinity;
      let maxX = -Infinity;
      let maxY = -Infinity;

      points.forEach(function (pos) {
        minX = Math.min(minX, pos.x);
        minY = Math.min(minY, pos.y);
        maxX = Math.max(maxX, pos.x);
        maxY = Math.max(maxY, pos.y);
      });

      const padding = 95;
      const centerX = (minX + maxX) / 2;
      const centerY = (minY + maxY) / 2;
      const radiusX = Math.max((maxX - minX) / 2 + padding, 170);
      const radiusY = Math.max((maxY - minY) / 2 + padding, 140);

      ctx.save();
      ctx.beginPath();
      ctx.ellipse(centerX, centerY, radiusX, radiusY, 0, 0, Math.PI * 2);
      ctx.fillStyle = cluster.color.bg;
      ctx.fill();
      ctx.lineWidth = 3;
      ctx.strokeStyle = cluster.color.border;
      ctx.stroke();
      ctx.restore();
    });
  });

  network.on('afterDrawing', function (ctx) {
    clusterIds.forEach(function (clusterId) {
      const cluster = clusters[clusterId];
      const ids = Array.from(cluster.nodes);
      const positions = network.getPositions(ids);
      const points = Object.values(positions);

      if (!points.length) return;

      let minX = Infinity;
      let minY = Infinity;
      let maxX = -Infinity;

      points.forEach(function (pos) {
        minX = Math.min(minX, pos.x);
        minY = Math.min(minY, pos.y);
        maxX = Math.max(maxX, pos.x);
      });

      const labelX = minX + 15;
      const labelY = minY - 55;
      const label = `Cluster ${cluster.index + 1} · Score ${cluster.score}/100`;

      ctx.save();

      ctx.font = 'bold 22px Arial';
      const textWidth = ctx.measureText(label).width;

      ctx.fillStyle = 'rgba(255,255,255,.92)';
      ctx.strokeStyle = cluster.color.border;
      ctx.lineWidth = 2;

      const boxWidth = textWidth + 28;
      const boxHeight = 42;
      const radius = 14;

      ctx.beginPath();
      ctx.moveTo(labelX + radius, labelY);
      ctx.lineTo(labelX + boxWidth - radius, labelY);
      ctx.quadraticCurveTo(labelX + boxWidth, labelY, labelX + boxWidth, labelY + radius);
      ctx.lineTo(labelX + boxWidth, labelY + boxHeight - radius);
      ctx.quadraticCurveTo(labelX + boxWidth, labelY + boxHeight, labelX + boxWidth - radius, labelY + boxHeight);
      ctx.lineTo(labelX + radius, labelY + boxHeight);
      ctx.quadraticCurveTo(labelX, labelY + boxHeight, labelX, labelY + boxHeight - radius);
      ctx.lineTo(labelX, labelY + radius);
      ctx.quadraticCurveTo(labelX, labelY, labelX + radius, labelY);
      ctx.closePath();

      ctx.fill();
      ctx.stroke();

      ctx.fillStyle = cluster.color.text;
      ctx.fillText(label, labelX + 14, labelY + 28);

      ctx.restore();
    });
  });

  function resetGraphStyle() {
    const nodeUpdates = graphData.nodes.map(function (node) {
      return {
        id: node.id,
        color: getNodeColor(node),
        borderWidth: getNodeBorderWidth(node),
        size: getNodeSize(node),
        font: {
          size: isPillar(node) ? 16 : 13,
          color: '#1f2937',
          face: 'Arial',
          bold: isPillar(node),
          strokeWidth: 3,
          strokeColor: '#ffffff'
        }
      };
    });

    const edgeUpdates = edges.get().map(function (edge) {
      const sameCluster =
        nodeClusterMap[edge.from] &&
        nodeClusterMap[edge.from] === nodeClusterMap[edge.to];

      return {
        id: edge.id,
        width: sameCluster ? 1.8 : 1,
        color: {
          color: sameCluster ? '#64748b' : '#cbd5e1',
          opacity: sameCluster ? 0.55 : 0.25
        }
      };
    });

    nodes.update(nodeUpdates);
    edges.update(edgeUpdates);
  }

  function highlightNode(nodeId) {
    const related = connectedNodes[nodeId] || new Set();
    const visibleNodes = new Set([nodeId]);

    related.forEach(function (id) {
      visibleNodes.add(id);
    });

    const nodeUpdates = graphData.nodes.map(function (node) {
      const isActive = visibleNodes.has(node.id);

      return {
        id: node.id,
        color: isActive ? getNodeColor(node) : {
          background: '#f1f5f9',
          border: '#cbd5e1',
          highlight: {
            background: '#e2e8f0',
            border: '#94a3b8'
          }
        },
        font: {
          size: isActive ? (isPillar(node) ? 17 : 15) : 11,
          color: isActive ? '#0f172a' : '#94a3b8',
          face: 'Arial',
          bold: isPillar(node),
          strokeWidth: 3,
          strokeColor: '#ffffff'
        },
        borderWidth: isActive ? Math.max(getNodeBorderWidth(node), 3) : 1
      };
    });

    const edgeUpdates = edges.get().map(function (edge) {
      const isConnected = edge.from === nodeId || edge.to === nodeId;

      return {
        id: edge.id,
        width: isConnected ? 3 : 1,
        color: {
          color: isConnected ? '#2563eb' : '#cbd5e1',
          opacity: isConnected ? 0.95 : 0.15
        }
      };
    });

    nodes.update(nodeUpdates);
    edges.update(edgeUpdates);
  }

  function updateInfoPanel(nodeId) {
    const panel = document.getElementById('cma-graph-info');
    if (!panel) return;

    const node = nodesIndex[nodeId];
    if (!node) return;

    const incoming = incomingCount[nodeId] || 0;
    const outgoing = outgoingCount[nodeId] || 0;
    const typeLabel = node.type === 'page' ? (i18n.page || 'Page') : (i18n.post || 'Post');

    const clusterId = nodeClusterMap[nodeId];
    const cluster = clusterId ? clusters[clusterId] : null;
    const nodeUrl = safeExternalUrl(node.url);

    panel.innerHTML = `
      <div class="cma-graph-card">
        <h3>${escapeHtml(node.label || node.title || (i18n.untitled || 'Untitled'))}</h3>
        <p><strong>${escapeHtml(i18n.type || 'Type')}:</strong> ${escapeHtml(typeLabel)}</p>
        <p><strong>${escapeHtml(i18n.incomingLinks || 'Incoming links')}:</strong> ${incoming}</p>
        <p><strong>${escapeHtml(i18n.outgoingLinks || 'Outgoing links')}:</strong> ${outgoing}</p>
        ${isPillar(node) ? `<p><strong>${escapeHtml(i18n.role || 'Role')}:</strong> ${escapeHtml(i18n.pillarPage || 'Pillar page')}</p>` : ''}
        ${cluster ? `<p><strong>${escapeHtml(i18n.cluster || 'Cluster')}:</strong> ${escapeHtml(cluster.label)}</p>` : ''}
        ${cluster ? `<p><strong>${escapeHtml(i18n.clusterScore || 'Cluster score')}:</strong> ${cluster.score}/100</p>` : ''}
        ${node.is_isolated ? `<p class="cma-warning"><strong>${escapeHtml(i18n.warning || 'Warning')}:</strong> ${escapeHtml(i18n.isolatedContent || 'Isolated content')}.</p>` : ''}
        ${nodeUrl ? `<p><a href="${escapeHtml(nodeUrl)}" target="_blank" rel="noopener noreferrer">${escapeHtml(i18n.openContent || 'Open content')}</a></p>` : ''}
      </div>
    `;
  }

  network.once('stabilized', function () {
    loader.style.display = 'none';
    network.setOptions({ physics: false });

    network.fit({
      animation: {
        duration: 800,
        easingFunction: 'easeInOutQuad'
      }
    });
  });

  setTimeout(function () {
    loader.style.display = 'none';
    network.setOptions({ physics: false });
  }, 5000);

  network.on('click', function (params) {
    if (!params.nodes.length) {
      resetGraphStyle();
      return;
    }

    const nodeId = params.nodes[0];

    highlightNode(nodeId);
    updateInfoPanel(nodeId);

    network.focus(nodeId, {
      scale: 1.35,
      animation: {
        duration: 650,
        easingFunction: 'easeInOutQuad'
      }
    });
  });

  network.on('doubleClick', function () {
    resetGraphStyle();

    network.fit({
      animation: {
        duration: 700,
        easingFunction: 'easeInOutQuad'
      }
    });
  });
})();


function cmaToast(message, type = 'info', duration = 4000) {

    const container = document.getElementById('cma-toast-container');

    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `cma-toast cma-toast-${type}`;

    const toastMessage = document.createElement('span');
    toastMessage.textContent = String(message || '');
    const closeButton = document.createElement('span');
    closeButton.className = 'cma-toast-close';
    closeButton.innerHTML = '&times;';
    toast.append(toastMessage, closeButton);

    container.appendChild(toast);

    // Fermeture manuelle
    toast.querySelector('.cma-toast-close').addEventListener('click', () => {
        removeToast(toast);
    });

    // Auto suppression
    setTimeout(() => {
        removeToast(toast);
    }, duration);
}

function removeToast(toast) {
    toast.classList.add('cma-toast-out');
    setTimeout(() => toast.remove(), 300);
}

window.addEventListener("load", () => {

  document.querySelectorAll(".score-card").forEach((card, index) => {

    setTimeout(() => {

      // 🔥 Transition skeleton → contenu
      card.classList.remove("loading");
      card.classList.add("loaded");

      const gauge = card.querySelector(".site-health");
      if (!gauge) return;

      let value = parseInt(gauge.dataset.value) || 0;
      value = Math.max(0, Math.min(100, value));

      const arc = gauge.querySelector(".gauge-value");
      const sep = gauge.querySelector(".gauge-separator");
      const score = gauge.querySelector(".score");

      let current = 0;

      // 🎨 Couleur dynamique
      if (value < 40) {
        arc.style.stroke = "#e63946";
      } else if (value < 80) {
        arc.style.stroke = "#f4a261";
      } else {
        arc.style.stroke = "#2a9d8f";
      }

      const animate = () => {
        if (current <= value) {

          arc.style.strokeDasharray = current + " 100";
          sep.style.strokeDasharray = "1 99";
          sep.style.strokeDashoffset = -current;

          score.textContent = current + "%";

          current++;
          requestAnimationFrame(animate);
        }
      };

      animate();

    }, 800 + index * 200); // 👈 effet cascade propre
  });

});
