                                        }
                                        var blueAnchorRow = blueAnchorCell ? blueAnchorCell.closest('tr.data-row') : null;
                                        if (blueAnchorRow) showRowActionsDots(blueAnchorRow, true, blueAnchorCell);
                                        else hideRowActionsDots();
                                    } else if (singleCellSelection && tr && !tr.classList.contains('bg-blue-100') && !tr.classList.contains('grand-total-row') && !tr.classList.contains('kpi-finalize-total-row') && String(tr.getAttribute('data-manual-total-row') || '') !== '1' && rowActionsPopover) {
                                        showRowActionsDots(tr, false, lastCell);
                                    } else {
                                        hideRowActionsPopover();
                                        hideRowActionsDots();
                                    }
                                    var runPosition = function() {
                                        // Use viewport coordinates (popover is position:fixed).
                                        var vw = window.innerWidth || document.documentElement.clientWidth;
                                        var vh = window.innerHeight || document.documentElement.clientHeight;
                                        var cellRect = lastCell.getBoundingClientRect();
                                        var gap = 8;
                                        var popW = selectionPopover.offsetWidth || 220;
                                        var popH = selectionPopover.offsetHeight || 120;
                                        var cellLeft   = cellRect.left;
                                        var cellTop    = cellRect.top;
                                        var cellRight  = cellRect.right;
                                        var cellBottom = cellRect.bottom;
                                        var cellCenterY = cellTop + (cellRect.height / 2);
                                        var positions = [
                                            { left: cellRight + gap,        top: cellCenterY - popH / 2, name: 'right' },
                                            { left: cellLeft - popW - gap,  top: cellCenterY - popH / 2, name: 'left' },
                                            { left: vw - popW - gap,        top: gap,                    name: 'topRight' },
                                            { left: gap,                    top: gap,                    name: 'topLeft' },
                                            { left: cellLeft,               top: cellBottom + gap,       name: 'below' },
                                            { left: cellLeft,               top: cellTop - popH - gap,   name: 'above' }
                                        ];
                                        var best = null;
                                        for (var i = 0; i < positions.length; i++) {
                                            var p = positions[i];
                                            var l = Math.max(gap, Math.min(vw - popW - gap, p.left));
                                            var t = Math.max(gap, Math.min(vh - popH - gap, p.top));
                                            var popRight  = l + popW;
                                            var popBottom = t + popH;
                                            var overlaps = !(popRight < cellLeft || l > cellRight || popBottom < cellTop || t > cellBottom);
                                            if (!overlaps) { best = { left: l, top: t }; break; }
                                        }
                                        if (!best) best = { left: vw - popW - gap, top: gap };
                                        selectionPopover.style.left = best.left + 'px';
                                        selectionPopover.style.top  = best.top  + 'px';
                                    };
                                    if (selectionPopover && shouldShowSelectionPopover) requestAnimationFrame(runPosition);
                                }
                                var rowTotalColPlanCached = null;
                                function fieldColIsPerformanceMetricIdx(colIdx) {
                                    if (colIdx < 0 || colIdx >= fields.length) return true;
                                    var f = fields[colIdx] || {};
                                    var metricToken = String((getFieldKey(f) || '') + '_' + (f.label || '')).toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
                                    return metricToken.indexOf('variance') !== -1
                                        || (metricToken.indexOf('rate') !== -1 && (metricToken.indexOf('accomp') !== -1 || metricToken.indexOf('accomplishment') !== -1))
                                        || metricToken.indexOf('descriptive') !== -1
                                        || metricToken.indexOf('rating') !== -1;
                                }
                                function gridParentLabelNorm(f) {
                                    return String(f._grid_parent_label || '').trim().toLowerCase();
                                }
                                function isGrandTotalHeaderGroupField(f) {
                                    var p = gridParentLabelNorm(f);
                                    return p.indexOf('grand') !== -1 && p.indexOf('total') !== -1;
                                }
                                function isRowTotalHeaderGroupField(f) {
                                    var p = gridParentLabelNorm(f);
                                    if (isGrandTotalHeaderGroupField(f)) return false;
                                    return /\btotal\b/.test(p);
                                }
                                function isSkippableRowTotalSourceField(f) {
                                    var p = gridParentLabelNorm(f);
                                    if (/\bquarter\b/.test(p) || /\bq[1-4]\b/.test(p)) return true;
                                    if (p.replace(/[^a-z0-9]/g, '') === 'no') return true;
                                    return false;
                                }
                                function getRowTotalColumnPlan() {
                                    if (rowTotalColPlanCached) return rowTotalColPlanCached;
                                    var bySub = {};
                                    function ensureSub(s) {
                                        var k = String(s);
                                        if (!bySub[k]) bySub[k] = { sources: [], target: null };
                                    }
                                    for (var i = 0; i < fields.length; i++) {
                                        var f = fields[i] || {};
                                        if (f.type !== 'number' || !f._grid_is_subcolumn) continue;
                                        if (fieldColIsPerformanceMetricIdx(i)) continue;
                                        var sub = parseInt(String(f._grid_subcolumn_index), 10);
                                        if (sub !== 0 && sub !== 1) continue;
                                        if (isRowTotalHeaderGroupField(f)) {
                                            ensureSub(sub);
                                            bySub[String(sub)].target = i;
                                        }
                                    }
                                    for (var j = 0; j < fields.length; j++) {
                                        var f2 = fields[j] || {};
                                        if (f2.type !== 'number' || !f2._grid_is_subcolumn) continue;
                                        if (fieldColIsPerformanceMetricIdx(j)) continue;
                                        var sub2 = parseInt(String(f2._grid_subcolumn_index), 10);
                                        if (sub2 !== 0 && sub2 !== 1) continue;
                                        if (isRowTotalHeaderGroupField(f2) || isGrandTotalHeaderGroupField(f2)) continue;
                                        if (isSkippableRowTotalSourceField(f2)) continue;
                                        ensureSub(sub2);
                                        if (bySub[String(sub2)].target === j) continue;
                                        bySub[String(sub2)].sources.push(j);
                                    }
                                    var grandTotalColIdx = -1;
                                    for (var g = 0; g < fields.length; g++) {
                                        var gf = fields[g] || {};
                                        var gftype = String(gf.type || 'text').toLowerCase();
                                        if (gftype !== 'number' && gftype !== 'text') continue;
                                        if (fieldColIsPerformanceMetricIdx(g)) continue;
                                        if (isGrandTotalHeaderGroupField(gf)) {
                                            grandTotalColIdx = g;
                                            break;
                                        }
                                        if (!gf._grid_is_subcolumn) {
                                            var glab = String(gf.label || '').toLowerCase();
                                            var gkey = String(getFieldKey(gf) || '').toLowerCase();
                                            var gcomb = glab + ' ' + gkey;
                                            if (gcomb.indexOf('grand') !== -1 && gcomb.indexOf('total') !== -1) {
                                                grandTotalColIdx = g;
                                                break;
                                            }
                                            var gkCompact = gkey.replace(/_/g, '');
                                            if (gkey.indexOf('grand_total') !== -1 || gkCompact.indexOf('grandtotal') !== -1) {
                                                grandTotalColIdx = g;
                                                break;
                                            }
                                        }
                                    }
                                    rowTotalColPlanCached = { bySub: bySub, grandTotalColIdx: grandTotalColIdx };
                                    return rowTotalColPlanCached;
                                }
                                function formatRowTotalCellDisplay(n) {
                                    var x = Number(n);
                                    if (!isFinite(x)) return '0';
                                    var r2 = Math.round(x * 100) / 100;
                                    if (Math.abs(r2 - Math.round(r2)) < 1e-9) return String(Math.round(r2));
                                    var s = r2.toFixed(2);
                                    if (s.indexOf('.') !== -1) s = s.replace(/0+$/, '').replace(/\.$/, '');
                                    return s;
                                }
                                function applyRowTotalsFromYearColumns(tr) {
                                    if (!tr || !tableBody) return 0;
                                    if (tr.classList.contains('bg-blue-100') || tr.classList.contains('grand-total-row') || tr.classList.contains('kpi-finalize-total-row')) {
                                        if (typeof window.showToast === 'function') window.showToast('notice', 'Calculate works on white data rows.');
                                        return 0;
                                    }
                                    var plan = getRowTotalColumnPlan();
                                    var bySub = plan.bySub || {};
                                    var grandTotalColIdx = typeof plan.grandTotalColIdx === 'number' ? plan.grandTotalColIdx : -1;
                                    var tds = window.getRowTdCells(tr);
                                    var updated = 0;
                                    var wroteGrandTotal = false;
                                    var firstTd = null;
                                    ['0', '1'].forEach(function(sk) {
                                        var p = bySub[sk];
                                        if (!p || p.target === null || !p.sources.length) return;
                                        var sum = 0;
                                        var any = false;
                                        for (var si = 0; si < p.sources.length; si++) {
                                            var ci = p.sources[si];
                                            var td = tds[ci];
                                            if (!td) continue;
                                            var read = readValueFromTd(td);
                                            if (!read) continue;
                                            var n = toNumeric(read.value);
                                            if (!isNaN(n)) { sum += n; any = true; }
                                        }
                                        if (!any) return;
                                        var targetTd = tds[p.target];
                                        if (!targetTd) return;
                                        var readT = readValueFromTd(targetTd);
                                        if (!readT) return;
                                        var formatted = formatRowTotalCellDisplay(sum);
                                        if (writeValueToTd(targetTd, readT.fieldKey, formatted)) {
                                            updated++;
                                            if (!firstTd) firstTd = targetTd;
                                            var aEl = targetTd.querySelector('a[href]');
                                            if (aEl && formatted) aEl.setAttribute('href', formatted);
                                        }
                                    });
                                    if (grandTotalColIdx >= 0 && grandTotalColIdx < tds.length) {
                                        var rowSum = 0;
                                        var anyGt = false;
                                        ['0', '1'].forEach(function(sk2) {
                                            var p2 = bySub[sk2];
                                            if (!p2 || p2.target === null) return;
                                            var tdTot = tds[p2.target];
                                            if (!tdTot) return;
                                            var r2 = readValueFromTd(tdTot);
                                            if (!r2) return;
                                            var n2 = toNumeric(r2.value);
                                            if (!isNaN(n2)) { rowSum += n2; anyGt = true; }
                                        });
                                        if (anyGt) {
                                            var gtTd = tds[grandTotalColIdx];
                                            var readGt = gtTd ? readValueFromTd(gtTd) : null;
                                            if (readGt) {
                                                var fmtGt = formatRowTotalCellDisplay(rowSum);
                                                if (writeValueToTd(gtTd, readGt.fieldKey, fmtGt)) {
                                                    updated++;
                                                    wroteGrandTotal = true;
                                                    if (!firstTd) firstTd = gtTd;
                                                    var aGt = gtTd.querySelector('a[href]');
                                                    if (aGt && fmtGt) aGt.setAttribute('href', fmtGt);
                                                }
                                            }
                                        }
                                    }
                                    if (updated > 0) {
                                        window.tableDataDirty = true;
                                        if (typeof scheduleRecomputeFormulas === 'function' && firstTd) scheduleRecomputeFormulas(firstTd);
                                        if (typeof recomputeAllGrandTotals === 'function') recomputeAllGrandTotals();
                                        if (typeof scheduleAutoSave === 'function') scheduleAutoSave();
                                        if (typeof setAutosaveStatus === 'function') setAutosaveStatus('saving');
                                        if (typeof window.showToast === 'function') {
                                            var msg = wroteGrandTotal
                                                ? 'Total M/F and Grand Total column updated. Summary rows refreshed.'
                                                : (updated >= 2 ? 'Totals updated (M & F). Summary rows refreshed.' : 'Total updated. Summary rows refreshed.');
                                            window.showToast('notice', msg);
                                        }
                                    } else {
                                        if (typeof window.showToast === 'function') {
                                            window.showToast('notice', 'No Total/year column mapping found, or no numeric values to sum.');
                                        }
                                    }
                                    return updated;
                                }
                                function showRowActionsForRow(tr, refCell) {
                                    if (!rowActionsPopover || !tableContainerMulti || !tr) return;
                                    if (tr.classList.contains('bg-blue-100') || tr.classList.contains('grand-total-row') || tr.classList.contains('kpi-finalize-total-row') || String(tr.getAttribute('data-manual-total-row') || '') === '1') return;
                                    if (rowActionsPopoverBlue) rowActionsPopoverBlue.classList.add('hidden');
                                    hoveredRowForActions = tr;
                                    rowActionsPopover.classList.remove('hidden');
                                    var addRowsBtn = document.getElementById('row-actions-add-rows-btn');
                                    if (addRowsBtn) addRowsBtn.style.display = '';
                                    positionRowActionsPopover(rowActionsPopover, tr, refCell);
                                }
                                function showRowActionsForBlueRow(tr, refCell) {
                                    if (!rowActionsPopoverBlue || !tableContainerMulti || !tr) return;
                                    // Blue summary rows and grand-total rows share the "Calculation" popover (a flow).
                                    if (!tr.classList.contains('bg-blue-100') && !tr.classList.contains('grand-total-row') && String(tr.getAttribute('data-manual-total-row') || '') !== '1') return;
                                    if (rowActionsPopover) rowActionsPopover.classList.add('hidden');
                                    hoveredRowForActions = tr;
                                    rowActionsBlueTargetRefCell = refCell || null;
                                    var isGrandTotalRow = tr.classList.contains('grand-total-row');
                                    var reselectBtn = document.getElementById('row-actions-reselect-cells-blue-btn');
                                    var addBlueBelowBtn = document.getElementById('row-actions-add-btn-blue');
                                    if (reselectBtn) reselectBtn.style.display = isGrandTotalRow ? 'none' : '';
                                    if (addBlueBelowBtn) addBlueBelowBtn.style.display = isGrandTotalRow ? 'none' : '';
                                    var openCalcBtn = document.getElementById('row-actions-open-calc-btn-blue');
                                    if (openCalcBtn) openCalcBtn.style.display = '';
                                    rowActionsPopoverBlue.classList.remove('hidden');
                                    positionRowActionsPopover(rowActionsPopoverBlue, tr, refCell);
                                }

                                function positionRowActionsDots(tr, refCell) {
                                    if (!rowActionsDotsBtn || !tr) return;
                                    var anchor = refCell || rowActionsDotsTargetRefCell;
                                    var rect = anchor && anchor.getBoundingClientRect ? anchor.getBoundingClientRect() : tr.getBoundingClientRect();
                                    // If the selected cell is outside the visible viewport, hide dots.
                                    // (Since dots are `position: fixed`, without hiding they will "stick" at the top/bottom.)
                                    var winRect = { top: 0, left: 0, right: window.innerWidth, bottom: window.innerHeight };
                                    var containerRect = tableContainerMulti && tableContainerMulti.getBoundingClientRect
                                        ? tableContainerMulti.getBoundingClientRect()
                                        : winRect;
                                    var intersectsWin = !(rect.bottom < winRect.top || rect.top > winRect.bottom || rect.right < winRect.left || rect.left > winRect.right);
                                    var intersectsContainer = !(rect.bottom < containerRect.top || rect.top > containerRect.bottom || rect.right < containerRect.left || rect.left > containerRect.right);
                                    if (!intersectsWin || !intersectsContainer || rect.width <= 0 || rect.height <= 0) {
                                        hideRowActionsDots();
                                        return;
                                    }
                                    // If previously hidden because cell was off-screen, show again once visible.
                                    rowActionsDotsBtn.classList.remove('hidden');
                                    rowActionsDotsBtn.style.display = 'flex';
                                    rowActionsDotsBtn.style.visibility = 'visible';
                                    var gap = 6;
                                    var btnSize = 32;
                                    var left = rect.right - btnSize - gap;
                                    if (left < gap) left = gap;
                                    var top = rect.top + (rect.height / 2) - (btnSize / 2);
                                    if (top < gap) top = gap;
                                    if (top + btnSize > window.innerHeight - gap) top = window.innerHeight - gap - btnSize;
                                    rowActionsDotsBtn.style.left = left + 'px';
                                    rowActionsDotsBtn.style.top = top + 'px';
                                }

                                function scheduleRepositionRowActionsDots() {
                                    // `position: fixed` must track the cell on every frame. Coalescing with "if rAF pending return"
                                    // skipped updates during fast scroll so the a lagged until scroll stopped.
                                    if (!rowActionsDotsBtn || !rowActionsDotsTargetRow) return;
                                    if (!tableContainerMulti) return;
                                    if (scheduleRepositionRowActionsDots._raf) {
                                        cancelAnimationFrame(scheduleRepositionRowActionsDots._raf);
                                    }
                                    scheduleRepositionRowActionsDots._raf = requestAnimationFrame(function() {
                                        scheduleRepositionRowActionsDots._raf = null;
                                        positionRowActionsDots(rowActionsDotsTargetRow, rowActionsDotsTargetRefCell || null);
                                    });
                                }
                                /** Immediate sync for scroll handlers (no rAF gap while compositor / main thread scroll). */
                                function syncRowActionsDotsToAnchorNow() {
                                    if (!rowActionsDotsBtn || !rowActionsDotsTargetRow) return;
                                    positionRowActionsDots(rowActionsDotsTargetRow, rowActionsDotsTargetRefCell || null);
                                }

                                function showRowActionsDots(tr, isBlue, refCell) {
                                    if (!rowActionsDotsBtn || !tr) return;
                                    // Always hide popovers when only showing dots.
                                    hideRowActionsPopover();
                                    rowActionsDotsTargetRow = tr;
                                    rowActionsDotsTargetIsBlue = !!isBlue;
                                    rowActionsDotsTargetRefCell = refCell || null;
                                    rowActionsDotsBtn.classList.remove('hidden');
                                    rowActionsDotsBtn.style.display = 'flex';
                                    rowActionsDotsBtn.style.visibility = 'visible';
                                    requestAnimationFrame(function() {
                                        positionRowActionsDots(tr, refCell || null);
                                    });
                                }

                                function hideRowActionsDots() {
                                    if (!rowActionsDotsBtn) return;
                                    rowActionsDotsBtn.classList.add('hidden');
                                    rowActionsDotsBtn.style.display = 'none';
                                }
                                if (rowActionsDotsBtn) {
                                    rowActionsDotsBtn.addEventListener('click', function(e) {
                                        e.preventDefault();
                                        e.stopPropagation();
                                        if (!rowActionsDotsTargetRow) return;
                                        var tr = rowActionsDotsTargetRow;
                                        var refCell = rowActionsDotsTargetRefCell;
                                        var isBlue = rowActionsDotsTargetIsBlue;
                                        hideRowActionsDots();
                                        if (isBlue) showRowActionsForBlueRow(tr, refCell);
                                        else showRowActionsForRow(tr, refCell);
                                    });
                                }

                                // Keep dots aligned while user scrolls (vertical + horizontal).
                                // Wide tables scroll inside .overflow-x-auto. Page vertical scroll is often on an ancestor
                                // with overflow-y:auto (see getScrollableForTable), not on #table-container-multi or window.
                                (function bindRowActionsDotsScrollSync() {
                                    var onScroll = function() { syncRowActionsDotsToAnchorNow(); };
                                    if (tableContainerMulti) {
                                        tableContainerMulti.addEventListener('scroll', onScroll, { passive: true });
                                        var xWrap = tableContainerMulti.querySelector('.overflow-x-auto');
                                        if (xWrap) xWrap.addEventListener('scroll', onScroll, { passive: true });
                                    }
                                    window.addEventListener('scroll', onScroll, { passive: true });
                                    var sp = typeof getScrollableForTable === 'function' ? getScrollableForTable() : null;
                                    if (sp && sp !== window && sp.addEventListener) {
                                        sp.addEventListener('scroll', onScroll, { passive: true });
                                    }
                                })();

                                // Keep dots aligned when layout shifts (e.g., sidebar open/close).
                                // Sidebar toggles often change widths via CSS transitions without firing window resize.
                                (function() {
                                    function kick() { scheduleRepositionRowActionsDots(); }
                                    try {
                                        if (typeof ResizeObserver !== 'undefined') {
                                            var ro = new ResizeObserver(function() { kick(); });
                                            // Observe both the table container and the root element for layout changes.
                                            if (tableContainerMulti) ro.observe(tableContainerMulti);
                                            if (document && document.documentElement) ro.observe(document.documentElement);
                                        }
                                    } catch (e) {}
                                    document.addEventListener('transitionend', function() { kick(); }, true);
                                    window.addEventListener('resize', function() { kick(); }, { passive: true });
                                })();
                                function positionRowActionsPopover(popoverEl, tr, refCell) {
                                    if (!popoverEl || !tr) return;
                                    var runPos = function() {
                                        // Anchor to the selected / focused cell - not the row's first <td>. On wide tables the first
                                        // column is often scrolled off-screen; using it clamps `left` to ~0 and sticks the popover to the viewport edge.
                                        var anchorTd = null;
                                        if (refCell && refCell.closest && refCell.closest('tr') === tr) anchorTd = refCell;
                                        if (!anchorTd) {
                                            var selTd = tr.querySelector('td.cell-selected');
                                            if (selTd) anchorTd = selTd;
                                        }
                                        if (!anchorTd && lastClickedCellMulti && lastClickedCellMulti.closest && lastClickedCellMulti.closest('tr') === tr) {
                                            anchorTd = lastClickedCellMulti;
                                        }
                                        if (!anchorTd) {
                                            var firstTd = tr.querySelector('td');
                                            if (firstTd) anchorTd = firstTd;
                                        }
                                        var refRect = anchorTd && anchorTd.getBoundingClientRect ? anchorTd.getBoundingClientRect() : tr.getBoundingClientRect();
                                        var popW = popoverEl.offsetWidth || 180;
                                        var popH = popoverEl.offsetHeight || 44;
                                        var gap = 8;
                                        var isFixed = popoverEl === rowActionsPopover;
                                        function clampNum(n, lo, hi) {
                                            return Math.max(lo, Math.min(hi, n));
                                        }
                                        // Prefer opening beside the cell (right, then left) so rows directly below stay visible;
                                        // only use below/above when horizontal space is tight.
                                        var top, left;
                                        if (isFixed) {
                                            var vw = window.innerWidth || document.documentElement.clientWidth;
                                            var vh = window.innerHeight || document.documentElement.clientHeight;
                                            var roomRight = refRect.right + gap + popW <= vw - gap;
                                            var roomLeft = refRect.left - gap - popW >= gap;
                                            var centerX = refRect.left + refRect.width / 2 - popW / 2;
                                            if (roomRight) {
                                                left = refRect.right + gap;
                                                top = clampNum(refRect.top, gap, vh - popH - gap);
                                            } else if (roomLeft) {
                                                left = refRect.left - popW - gap;
                                                top = clampNum(refRect.top, gap, vh - popH - gap);
                                            } else {
                                                top = refRect.bottom + gap;
                                                if (top + popH > vh - gap) top = refRect.top - popH - gap;
                                                top = clampNum(top, gap, vh - popH - gap);
                                                left = clampNum(centerX, gap, vw - popW - gap);
                                            }
                                            left = clampNum(left, gap, vw - popW - gap);
                                        } else {
                                            if (!tableContainerMulti) return;
                                            var containerRect = tableContainerMulti.getBoundingClientRect();
                                            var cw = containerRect.width;
                                            var ch = containerRect.height;
                                            var refL = refRect.left - containerRect.left;
                                            var refR = refRect.right - containerRect.left;
                                            var refT = refRect.top - containerRect.top;
                                            var refB = refRect.bottom - containerRect.top;
                                            var roomRightRel = refR + gap + popW <= cw - gap;
                                            var roomLeftRel = refL - gap - popW >= gap;
                                            var centerXRel = refL + refRect.width / 2 - popW / 2;
                                            if (roomRightRel) {
                                                left = refR + gap;
                                                top = clampNum(refT, gap, ch - popH - gap);
                                            } else if (roomLeftRel) {
                                                left = refL - popW - gap;
                                                top = clampNum(refT, gap, ch - popH - gap);
                                            } else {
                                                var topAbove = refT - popH - gap;
                                                var topBelow = refB + gap;
                                                top = topAbove >= gap ? topAbove : topBelow;
                                                top = clampNum(top, gap, ch - popH - gap);
                                                left = clampNum(centerXRel, gap, cw - popW - gap);
                                            }
                                            left = clampNum(left, gap, cw - popW - gap);
                                        }
                                        popoverEl.style.left = left + 'px';
                                        popoverEl.style.top = top + 'px';
                                    };
                                    requestAnimationFrame(runPos);
                                }
                                function hideRowActionsPopover() {
                                    if (rowActionsHideTimeout) clearTimeout(rowActionsHideTimeout);
                                    rowActionsHideTimeout = null;
                                    hoveredRowForActions = null;
                                    if (!pendingReselectCellsPick) {
                                        rowActionsBlueTargetRefCell = null;
                                    }
                                    if (rowActionsPopover) rowActionsPopover.classList.add('hidden');
                                    if (rowActionsPopoverBlue) rowActionsPopoverBlue.classList.add('hidden');
                                    var reselectBtn = document.getElementById('row-actions-reselect-cells-blue-btn');
                                    var addBlueBelowBtn = document.getElementById('row-actions-add-btn-blue');
                                    var openCalcBtn = document.getElementById('row-actions-open-calc-btn-blue');
                                    if (reselectBtn) reselectBtn.style.display = '';
                                    if (addBlueBelowBtn) addBlueBelowBtn.style.display = '';
                                    if (openCalcBtn) openCalcBtn.style.display = '';
                                }
                                function cancelPendingReselectCellsPick() {
                                    if (!pendingReselectCellsPick) return;
                                    pendingReselectCellsPick = false;
                                    reselectCellsTargetTd = null;
                                    rowActionsBlueTargetRefCell = null;
                                    if (document.body) document.body.classList.remove('uaps-reselect-cells-pick');
                                    if (typeof window.dismissUapsToastsByTag === 'function') window.dismissUapsToastsByTag('reselect-cells-pick');
                                }
                                function cloneBlueCellMappingDeep(m) {
                                    if (!m || typeof m !== 'object') return {};
                                    try {
                                        return JSON.parse(JSON.stringify(m));
                                    } catch (err) {
                                        return Object.assign({}, m);
                                    }
                                }
                                /** Map row_uids / row_indices from source section to target section by matching row positions in each block. */
                                function remapBlueFormulaMappingForTargetSection(cloned, sourceBlueRow, targetBlueRow) {
                                    if (!cloned || !sourceBlueRow || !targetBlueRow) return cloned;
                                    var srcSec = findSectionRowsContainingRow(sourceBlueRow);
                                    var tgtSec = findSectionRowsContainingRow(targetBlueRow);
                                    if (!srcSec || !tgtSec || srcSec.length === 0 || tgtSec.length === 0) return cloned;
                                    var srcData = srcSec.filter(function(tr) { return !tr.classList.contains('bg-blue-100'); });
                                    var tgtData = tgtSec.filter(function(tr) { return !tr.classList.contains('bg-blue-100'); });
                                    var positions = [];
                                    var uids = Array.isArray(cloned.row_uids) ? cloned.row_uids : [];
                                    var idxs = Array.isArray(cloned.row_indices) ? cloned.row_indices : [];
                                    if (uids.length > 0) {
                                        var uidSet = {};
                                        uids.forEach(function(u) { uidSet[String(u)] = true; });
                                        srcData.forEach(function(tr, i) {
                                            var ru = String(tr.getAttribute('data-row-uid') || '');
                                            if (ru && uidSet[ru]) positions.push(i);
                                        });
                                    } else if (idxs.length > 0) {
                                        idxs.forEach(function(ri) {
                                            var n = parseInt(ri, 10);
                                            if (!isNaN(n) && n >= 0) positions.push(n);
                                        });
                                    }
                                    positions = positions.filter(function(i) { return i >= 0 && i < tgtData.length; });
                                    var newUids = [];
                                    positions.forEach(function(i) {
                                        var u = String(tgtData[i].getAttribute('data-row-uid') || '');
                                        if (u) newUids.push(u);
                                    });
                                    if (newUids.length > 0) {
                                        cloned.row_uids = newUids;
                                        delete cloned.row_indices;
                                    } else if (idxs.length > 0) {
                                        var ni = idxs.map(function(x) { return parseInt(x, 10); }).filter(function(n) { return !isNaN(n) && n >= 0 && n < tgtData.length; });
                                        if (ni.length > 0) {
                                            cloned.row_indices = ni;
                                            delete cloned.row_uids;
                                        }
                                    }
                                    try {
                                        cloned.section_ref = buildSectionRefFromRow(targetBlueRow);
                                    } catch (e2) {}
                                    return cloned;
                                }
                                /**
                                 * After copying a blue totalaTMs mapping, source_columns / sourceA / sourceB still pointed at the
                                 * source blue cellaTMs column. Shift every referenced field key by (targetCol - sourceCol) so
                                 * sums and highlights use the column above the target blue cell.
                                 */
                                function remapBlueFormulaMappingSourceColumnsForTargetBlueCell(mapping, sourceTd, targetTd) {
                                    if (!mapping || !sourceTd || !targetTd || !fields || fields.length === 0) return mapping;
                                    var srcCol = getColIndex(sourceTd);
                                    var tgtCol = getColIndex(targetTd);
                                    if (srcCol < 0 || tgtCol < 0 || srcCol === tgtCol) return mapping;
                                    var delta = tgtCol - srcCol;
                                    function shiftFieldKey(k) {
                                        if (k == null) return k;
                                        var s = String(k).trim();
                                        if (!s) return k;
                                        var idx = getFieldIndexByKeyFlexible(s);
                                        if (idx < 0) return k;
                                        var ni = idx + delta;
                                        if (ni < 0 || ni >= fields.length) return k;
                                        return getFieldKey(fields[ni]);
                                    }
                                    if (Array.isArray(mapping.source_columns) && mapping.source_columns.length > 0) {
                                        mapping.source_columns = mapping.source_columns.map(shiftFieldKey);
                                    }
                                    if (mapping.sourceA) mapping.sourceA = shiftFieldKey(mapping.sourceA);
                                    if (mapping.sourceB) mapping.sourceB = shiftFieldKey(mapping.sourceB);
                                    if (Array.isArray(mapping.source_keys) && mapping.source_keys.length > 0) {
                                        mapping.source_keys = mapping.source_keys.map(shiftFieldKey);
                                    }
                                    if (fields[tgtCol]) mapping.target_field = getFieldKey(fields[tgtCol]);
                                    return mapping;
                                }
                                /**
                                 * Mirror manual "Apply" on blue calculations: persist mapping to template summary_cell_mappings
                                 * (updateSummaryRules) so reload/hydrate and Planning Coordinator see the same formula + row scope.
                                 */
                                function persistCopiedBlueCellMappingToTemplate(targetTd, targetRow, merged) {
                                    if (!targetTd || !targetRow || !merged || typeof summaryRulesUrl === 'undefined') return;
                                    var uiCt = String(merged.ui_calc_type || '').trim();
                                    if (uiCt === 'grand-total') return;
                                    if (uiCt.indexOf('blue-row-formula-custom') !== -1) return;
                                    if (merged.custom_expr && String(merged.custom_expr).trim()) return;
                                    var tgtCol = getColIndex(targetTd);
                                    if (tgtCol < 0 || !fields[tgtCol]) return;
                                    var singleTargetKey = getFieldKey(fields[tgtCol]);
                                    var opRaw = String(merged.ui_formula_operation || merged.operation || '').trim();
                                    if (!opRaw) {
                                        if (uiCt === 'summary-formula') opRaw = 'sum';
                                        else opRaw = String(merged.ui_calc_type || 'sum').trim();
                                    }
                                    if (opRaw === 'summary-formula') opRaw = 'sum';
                                    var opMap = { unique: 'count_unique', unique_adjust: 'count_unique', countif: 'count_total', count_rows: 'count_rows', sum: 'sum', avg: 'avg', avg_number: 'avg', avg_percentage: 'avg' };
                                    var backendOp = opMap[opRaw] || opRaw;
                                    if (!backendOp) backendOp = 'sum';
                                    var primarySourceKey = '';
                                    if (Array.isArray(merged.source_columns) && merged.source_columns.length > 0) {
                                        primarySourceKey = String(merged.source_columns[0] || '').trim();
                                    }
                                    if (!primarySourceKey && merged.sourceA) primarySourceKey = String(merged.sourceA).trim();
                                    if (!primarySourceKey || !singleTargetKey) return;
                                    var outputPayload = {
                                        target_field: singleTargetKey,
                                        operation: backendOp,
                                        sourceA: primarySourceKey,
                                        ui_calc_type: uiCt || opRaw,
                                        ui_formula_operation: String(merged.ui_formula_operation || opRaw)
                                    };
                                    if (merged.sourceB) outputPayload.sourceB = String(merged.sourceB);
                                    if (Array.isArray(merged.source_columns) && merged.source_columns.length > 0) {
                                        outputPayload.source_columns = merged.source_columns.map(function(k) { return String(k); });
                                    }
                                    outputPayload.section_ref = String(merged.section_ref || '').trim() || buildSectionRefFromRow(targetRow);
                                    if (Array.isArray(merged.row_indices) && merged.row_indices.length > 0) {
                                        outputPayload.row_indices = merged.row_indices.map(function(x) { return parseInt(x, 10); }).filter(function(n) { return !isNaN(n); });
                                    }
                                    if (Array.isArray(merged.row_uids) && merged.row_uids.length > 0) {
                                        outputPayload.row_uids = merged.row_uids.slice();
                                    }
                                    if (merged.count_adjust != null && merged.count_adjust !== '' && (backendOp === 'count_unique' || opRaw === 'unique_adjust')) {
                                        var ca = parseInt(String(merged.count_adjust), 10);
                                        if (!isNaN(ca)) outputPayload.count_adjust = ca;
                                    }
                                    if (merged.suffix) outputPayload.suffix = String(merged.suffix);
                                    upsertSelectionMapping(outputPayload);
                                    var token = document.querySelector('meta[name="csrf-token"]');
                                    token = token ? token.getAttribute('content') : '';
                                    fetch(summaryRulesUrl, {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
                                        body: JSON.stringify({ output: outputPayload })
                                    }).then(function(r) { return r.json(); }).then(function(res) { if (typeof updateSummaryRulesCacheFromResponse === 'function') updateSummaryRulesCacheFromResponse(res); }).catch(function() {});
                                }
                                /**
                                 * Reselect Cells: copy only row_uids / row_indices from another blue total (remapped to this section).
                                 * The target cell keeps its existing operation, source columns, and formula - only which rows feed the calc changes.
                                 */
                                function applyBlueReselectCellsFromSourceToTarget(sourceTd, targetTd) {
                                    if (!sourceTd || !targetTd || !tableBody) return false;
                                    var sourceRow = sourceTd.closest('tr.data-row');
                                    var targetRow = targetTd.closest('tr.data-row');
                                    if (!sourceRow || !targetRow || !sourceRow.classList.contains('bg-blue-100') || !targetRow.classList.contains('bg-blue-100')) return false;
                                    if (sourceTd === targetTd) {
                                        if (typeof window.showToast === 'function') window.showToast('notice', 'Choose a different blue total to take row selection from.');
                                        return false;
                                    }
                                    var tgtMap = getCellFormulaMapping(targetTd);
                                    var targetFieldKey = '';
                                    if (!tgtMap) {
                                        var tci = getColIndex(targetTd);
                                        if (tci >= 0 && fields[tci]) {
                                            targetFieldKey = getFieldKey(fields[tci]);
                                            tgtMap = findSummaryOutputByTarget(targetFieldKey, targetRow);
                                        }
                                    }
                                    if (!targetFieldKey) {
                                        var tciMeta = getColIndex(targetTd);
                                        if (tciMeta >= 0 && fields[tciMeta]) targetFieldKey = getFieldKey(fields[tciMeta]);
                                    }
                                    if (targetFieldKey) {
                                        tgtMap = enrichMappingWithSavedMeta(tgtMap || {}, targetFieldKey, targetRow);
                                    }
                                    tgtMap = tgtMap ? cloneBlueCellMappingDeep(tgtMap) : {};
                                    var tgtHasCalc = !!(String(tgtMap.operation || '').trim() || String(tgtMap.ui_formula_operation || '').trim()
                                        || String(tgtMap.sourceA || '').trim()
                                        || (Array.isArray(tgtMap.source_columns) && tgtMap.source_columns.length > 0)
                                        || (Array.isArray(tgtMap.source_keys) && tgtMap.source_keys.length > 0)
                                        || String(tgtMap.ui_calc_type || '').trim());
                                    var srcMap = getCellFormulaMapping(sourceTd);
                                    if (!srcMap) {
                                        var sci0 = getColIndex(sourceTd);
                                        if (sci0 >= 0 && fields[sci0]) {
                                            srcMap = findSummaryOutputByTarget(getFieldKey(fields[sci0]), sourceRow);
                                        }
                                    }
                                    srcMap = enrichMappingWithTemplateRowScope(sourceTd, sourceRow, cloneBlueCellMappingDeep(srcMap || {}));
                                    var scopeCarrier = {
                                        row_uids: Array.isArray(srcMap.row_uids) ? srcMap.row_uids.slice() : [],
                                        row_indices: Array.isArray(srcMap.row_indices) ? srcMap.row_indices.slice() : []
                                    };
                                    var hasScope = (scopeCarrier.row_uids.length > 0) || (scopeCarrier.row_indices.length > 0);
                                    if (!hasScope) {
                                        if (typeof window.showToast === 'function') window.showToast('notice', 'That total has no saved row selection. Open Calculation on that cell and click Apply first.');
                                        return false;
                                    }
                                    var remappedScope = remapBlueFormulaMappingForTargetSection(cloneBlueCellMappingDeep(scopeCarrier), sourceRow, targetRow);
                                    var merged = cloneBlueCellMappingDeep(tgtMap);
                                    if (!tgtHasCalc) {
                                        // Allow empty blue cells to borrow only the row scope first.
                                        // User can open Calculation and Apply afterwards.
                                        if (targetFieldKey) merged.target_field = targetFieldKey;
                                        // Also copy/remap source column references so opening Calculation can
                                        // immediately highlight the mirrored cells in the opposite column.
                                        var previewMap = remapBlueFormulaMappingSourceColumnsForTargetBlueCell(
                                            cloneBlueCellMappingDeep(srcMap || {}),
                                            sourceTd,
                                            targetTd
                                        ) || {};
                                        if (Array.isArray(previewMap.source_columns) && previewMap.source_columns.length > 0) {
                                            merged.source_columns = previewMap.source_columns.slice();
                                        }
                                        if (previewMap.sourceA) merged.sourceA = String(previewMap.sourceA);
                                        if (previewMap.sourceB) merged.sourceB = String(previewMap.sourceB);
                                        if (Array.isArray(previewMap.source_keys) && previewMap.source_keys.length > 0) {
                                            merged.source_keys = previewMap.source_keys.slice();
                                        }
                                    }
                                    if (Array.isArray(remappedScope.row_uids) && remappedScope.row_uids.length > 0) {
                                        merged.row_uids = remappedScope.row_uids.slice();
                                        delete merged.row_indices;
                                    } else if (Array.isArray(remappedScope.row_indices) && remappedScope.row_indices.length > 0) {
                                        merged.row_indices = remappedScope.row_indices.map(function(x) { return parseInt(x, 10); }).filter(function(n) { return !isNaN(n); });
                                        delete merged.row_uids;
                                    } else {
                                        if (typeof window.showToast === 'function') window.showToast('notice', 'Could not map that row selection to this section.');
                                        return false;
                                    }
                                    try {
                                        merged.section_ref = buildSectionRefFromRow(targetRow);
                                    } catch (e) {}
                                    targetTd.classList.remove('manual-override');
                                    targetTd.removeAttribute('data-manual-override');
                                    setCellFormulaMapping(targetTd, merged);
                                    if (tgtHasCalc) {
                                        persistCopiedBlueCellMappingToTemplate(targetTd, targetRow, merged);
                                    }
                                    var sec = findSectionRowsContainingRow(targetRow);
                                    if (sec && sec.length) recomputeBlueRowFormulasInSection(sec);
                                    if (typeof recomputeAllGrandTotals === 'function') recomputeAllGrandTotals();
                                    if (typeof recomputeBlueRowPerformance === 'function') recomputeBlueRowPerformance(targetRow);
                                    window.tableDataDirty = true;
                                    if (typeof setAutosaveStatus === 'function') setAutosaveStatus('saving');
                                    // Immediately show copied source-cell selection so user does not need
                                    // to click the blue target again after using Reselect Cells.
                                    var didAutoSelect = false;
                                    if (typeof autoSelectSourcesForBlueCell === 'function') {
                                        didAutoSelect = !!autoSelectSourcesForBlueCell(targetTd, { silent: true });
                                    }
                                    if (!didAutoSelect) {
                                        clearSelectionMulti();
                                        setCellSelected(targetRow, targetTd, true);
                                        lastClickedRowMulti = targetRow;
                                        lastClickedCellMulti = targetTd;
                                        updateFormulaButtonState();
                                    } else {
                                        lastClickedRowMulti = targetRow;
                                        lastClickedCellMulti = targetTd;
                                        updateFormulaButtonState();
                                        if (targetTd && targetTd.scrollIntoView) {
                                            try {
                                                if (typeof requestAnimationFrame === 'function') {
                                                    requestAnimationFrame(function() {
                                                        requestAnimationFrame(function() {
                                                            try { targetTd.scrollIntoView({ block: 'nearest', behavior: 'auto' }); } catch (e) {}
                                                        });
                                                    });
                                                } else {
                                                    targetTd.scrollIntoView({ block: 'nearest', behavior: 'auto' });
                                                }
                                            } catch (e) {}
                                        }
                                    }
                                    if (typeof window.showToast === 'function') {
                                        if (tgtHasCalc) {
                                            window.showToast('success', 'Row selection updated. Your calculation for this cell is unchanged.');
                                        } else {
                                            window.showToast('success', 'Row selection copied. Now open Calculation and click Apply for this cell.');
                                        }
                                    }
                                    if (typeof window.performSaveTableData === 'function') {
                                        var doSaveCopy = function() {
                                            window.performSaveTableData({
                                                onSuccess: function() { if (typeof setAutosaveStatus === 'function') setAutosaveStatus('saved'); },
                                                onDone: function() {}
                                            });
                                        };
                                        if (typeof requestAnimationFrame === 'function') {
                                            requestAnimationFrame(function() { doSaveCopy(); });
                                        } else {
                                            setTimeout(doSaveCopy, 0);
                                        }
                                    } else if (typeof scheduleAutoSave === 'function') {
                                        scheduleAutoSave();
                                    }
                                    suppressNextTableClickAfterReselectCellsPick = true;
                                    if (typeof setTimeout === 'function') {
                                        setTimeout(function() { suppressNextTableClickAfterReselectCellsPick = false; }, 0);
                                    }
                                    return true;
                                }
                                function addNewRowMulti(insertAfterRow) {
                                    // Guard: if called directly from an event listener, insertAfterRow may be a MouseEvent
                                    if (insertAfterRow && typeof insertAfterRow.getAttribute !== 'function') insertAfterRow = null;
                                    if (!tableBody || coordinatorBlocks.length === 0) return;
                                    window.tableDataDirty = true;
                                    var lastBlock = coordinatorBlocks[coordinatorBlocks.length - 1];
                                    var subId = (lastBlock.submission_id != null && lastBlock.submission_id !== '') ? String(lastBlock.submission_id) : '';
                                    var userId = (lastBlock.user_id != null && lastBlock.user_id !== '') ? String(lastBlock.user_id) : '';
                                    var templateRow, insertAfter;
                                    var primarySelected = insertAfterRow || getPrimarySelectedRowMulti();
                                    if (primarySelected) {
                                        subId = primarySelected.getAttribute('data-submission-id') || '';
                                        userId = primarySelected.getAttribute('data-user-id') || '';
                                        if (primarySelected.classList.contains('bg-blue-100')) {
                                            // Add Blue Row: insert a new blue summary row (not a data row), aligned to campus/coordinator block
                                            insertAfter = primarySelected;
                                            if (!subId && !userId) {
                                                var prev = primarySelected.previousElementSibling;
                                                while (prev) {
                                                    if (prev.classList.contains('section-header-row')) {
                                                        subId = prev.getAttribute('data-submission-id') || '';
                                                        userId = prev.getAttribute('data-user-id') || '';
                                                        break;
                                                    }
                                                    prev = prev.previousElementSibling;
                                                }
                                            }
                                            var cloneSource = primarySelected;
                                            var cloneCells = window.getRowTdCells(cloneSource);
                                            var colCount = fields && fields.length > 0 ? fields.length : cloneCells.length;
                                            var newRow;
                                            if (cloneCells.length === colCount) {
                                                newRow = cloneSource.cloneNode(true);
                                                if (!newRow.classList.contains('group')) newRow.classList.add('group');
                                                newRow.querySelectorAll('input, select, textarea').forEach(function(el) {
                                                    if (el.tagName === 'SELECT') el.selectedIndex = 0; else el.value = '';
                                                });
                                                newRow.querySelectorAll('span').forEach(function(s) { s.textContent = ''; });
                                                window.getRowTdCells(newRow).forEach(function(td) {
                                                    td.removeAttribute('data-formula-source-columns');
                                                    td.removeAttribute('data-formula-row-uids');
                                                    td.removeAttribute('data-formula-row-indices');
                                                    td.removeAttribute('data-formula-source-a');
                                                    td.removeAttribute('data-formula-source-b');
                                                    td.removeAttribute('data-formula-source-keys');
                                                    td.removeAttribute('data-formula-section-ref');
                                                    td.removeAttribute('data-formula-ui-calc-type');
                                                    td.removeAttribute('data-formula-ui-formula-operation');
                                                    td.removeAttribute('data-manual-override');
                                                });
                                            } else {
                                                newRow = document.createElement('tr');
                                                newRow.className = 'data-row bg-blue-100 group border-l-4 border-indigo-200';
                                                var stickySlotByIndex = window.performanceStickySlotByIndex || {};
                                                var lastColForDelete = tableBody ? parseInt(tableBody.getAttribute('data-last-visible-col-index') || '-1', 10) : -1;
                                                if (lastColForDelete < 0) lastColForDelete = colCount - 1;
                                                for (var ci = 0; ci < colCount; ci++) {
                                                    var cell = document.createElement('td');
                                                    cell.setAttribute('data-field-col', String(ci));
                                                    var field = fields && fields[ci] ? fields[ci] : null;
                                                    var keyF = field ? getFieldKey(field) : '';
                                                    var labelNorm = (keyF || '').toLowerCase().replace(/[^a-z0-9]+/g, '');
                                                    var isNoCol = labelNorm === 'no';
                                                    var isNumOrCalc = (field && ((field.type || '') === 'number' || (field.meta && field.meta.calc)));
                                                    var alignClass = isNumOrCalc && !isNoCol ? 'text-right' : (isNoCol ? 'text-center' : '');
                                                    var stickyClass = stickySlotByIndex[ci] != null ? ('sticky-perf sticky-perf-' + stickySlotByIndex[ci]) : '';
                                                    cell.className = 'px-4 py-1.5 border-r border-gray-200 bg-blue-100 ' + alignClass + ' ' + stickyClass;
                                                    if (ci === lastColForDelete) cell.classList.add('relative');
                                                    var content;
                                                    if (ci === 0) {
                                                        var span = document.createElement('span');
                                                        span.className = 'text-sm font-semibold text-gray-800';
                                                        span.textContent = '';
                                                        content = span;
                                                    } else {
                                                        var inp = document.createElement('input');
                                                        inp.type = 'text';
                                                        inp.className = 'w-full text-sm text-gray-900 border-0 focus:ring-0 focus:outline-none bg-transparent font-semibold';
                                                        inp.value = '';
                                                        if (keyF) inp.setAttribute('name', keyF);
                                                        content = inp;
                                                    }
                                                    if (ci === lastColForDelete) {
                                                        var wrap = document.createElement('div');
                                                        wrap.className = 'flex items-center gap-2 relative pr-8 min-h-[28px]';
                                                        wrap.appendChild(content);
                                                        var delBtn = document.createElement('button');
                                                        delBtn.type = 'button';
                                                        delBtn.className = 'delete-row-btn absolute right-0 top-1/2 -translate-y-1/2 w-7 h-7 flex items-center justify-center rounded text-red-600 hover:bg-red-50 hover:text-red-800 opacity-0 group-hover:opacity-100 transition-opacity text-lg font-bold leading-none';
                                                        delBtn.title = 'Delete row';
                                                        delBtn.textContent = 'A';
                                                        wrap.appendChild(delBtn);
                                                        cell.appendChild(wrap);
                                                    } else {
                                                        cell.appendChild(content);
                                                    }
                                                    newRow.appendChild(cell);
                                                }
                                            }
                                            newRow.setAttribute('data-submission-id', subId);
                                            newRow.setAttribute('data-row-type', 'summary');
                                            newRow.setAttribute('data-user-id', userId || '');
                                            insertAfter.insertAdjacentElement('afterend', newRow);
                                            if (typeof normalizeBlueRowDashes === 'function') normalizeBlueRowDashes();
                                            clearSelectionMulti();
                                            var firstCell = newRow.querySelector('td');
                                            if (firstCell && typeof setCellSelected === 'function') setCellSelected(newRow, firstCell, true);
                                            updateDeleteBtnMulti();
                                            scheduleAutoSave();
                                            setAutosaveStatus('saving');
                                            function triggerSave() {
                                                if (typeof window.performSaveTableData === 'function') {
                                                    window.performSaveTableData({
                                                        onSuccess: function() { setAutosaveStatus('saved'); },
                                                        onDone: function() {}
                                                    });
                                                }
                                            }
                                            triggerSave();
                                            setTimeout(triggerSave, 100);
                                            setTimeout(triggerSave, 400);
                                            setTimeout(triggerSave, 800);
                                            return;
                                        } else {
                                            templateRow = primarySelected;
                                            insertAfter = primarySelected;
                                        }
                                    }
                                    if (!templateRow && insertAfter) {
                                        var anyData = tableBody.querySelector('tr.data-row:not(.bg-blue-100)');
                                        if (anyData) templateRow = anyData;
                                    }
                                    if (!insertAfter && !templateRow) {
                                        var lastDataRows = tableBody.querySelectorAll('tr.data-row');
                                        var matchSub = function(tr) {
                                            if (tr.classList.contains('bg-blue-100')) return false;
                                            if (tr.classList.contains('grand-total-row')) return false;
                                            if (tr.classList.contains('separator-row')) return false;
                                            var s = tr.getAttribute('data-submission-id');
                                            var u = tr.getAttribute('data-user-id');
                                            if (subId) return s === subId;
                                            if (userId) return u === userId;
                                            // Both empty - match any regular data row
                                            return true;
                                        };
                                        for (var i = lastDataRows.length - 1; i >= 0; i--) {
                                            if (matchSub(lastDataRows[i])) {
                                                templateRow = lastDataRows[i];
                                                insertAfter = lastDataRows[i];
                                                break;
                                            }
                                        }
                                    }
                                    // Final fallback: use any non-blue, non-grand-total data row as template
                                    if (!templateRow) {
                                        var fallbackRows = tableBody.querySelectorAll('tr.data-row:not(.bg-blue-100):not(.grand-total-row):not(.kpi-finalize-total-row):not(.separator-row)');
                                        if (fallbackRows.length > 0) {
                                            templateRow = fallbackRows[fallbackRows.length - 1];
                                            insertAfter = fallbackRows[fallbackRows.length - 1];
                                        }
                                    }
                                    if (!templateRow) return;
                                    var newRow = document.createElement('tr');
                                    newRow.className = 'data-row hover:bg-gray-50 group border-l-4 border-indigo-200';
                                    newRow.setAttribute('data-submission-id', subId);
                                    if (userId) newRow.setAttribute('data-user-id', userId);
                                    newRow.setAttribute('data-row-uid', makeRowUid());
                                    var cells = window.getRowTdCells(templateRow);
                                    for (var i = 0; i < cells.length; i++) {
                                        var cell = cells[i].cloneNode(true);
                                        cell.setAttribute('data-field-col', String(i));
                                        // Clear all inputs/selects/textareas so new row never shows "1" or other template values
                                        var inputs = cell.querySelectorAll('input, select, textarea');
                                        inputs.forEach(function(input) {
                                            if (input.tagName === 'SELECT') input.selectedIndex = 0; else input.value = '';
                                        });
                                        newRow.appendChild(cell);
                                    }
                                    if (insertAfter) {
                                        insertAfter.insertAdjacentElement('afterend', newRow);
                                    } else {
                                        tableBody.appendChild(newRow);
                                    }
                                    clearSelectionMulti();
                                    var firstCell = newRow.querySelector('td');
                                    if (firstCell && typeof setCellSelected === 'function') setCellSelected(newRow, firstCell, true);
                                    updateDeleteBtnMulti();
                                    scheduleAutoSave();
                                    setTimeout(function() {
                                        if (typeof window.performSaveTableData === 'function') window.performSaveTableData();
                                    }, 150);
                                    return newRow;
                                }

                                function addMultipleNewRowsMulti(insertAfterRow, count) {
                                    var c = parseInt(count, 10);
                                    if (!tableBody || !fields) return;
                                    if (!Number.isFinite(c) || c <= 0) return;
                                    var anchor = insertAfterRow || null;
                                    for (var i = 0; i < c; i++) {
                                        var inserted = addNewRowMulti(anchor);
                                        if (inserted) anchor = inserted;
                                    }
                                }
                                function addSeparateRowMulti(insertAfterRow) {
                                    if (!tableBody || coordinatorBlocks.length === 0) return;
                                    window.tableDataDirty = true;
                                    var lastBlock = coordinatorBlocks[coordinatorBlocks.length - 1];
                                    var subId = (lastBlock.submission_id != null && lastBlock.submission_id !== '') ? String(lastBlock.submission_id) : '';
                                    var userId = (lastBlock.user_id != null && lastBlock.user_id !== '') ? String(lastBlock.user_id) : '';
                                    var templateRow, insertAfter;
                                    var primarySelected = insertAfterRow || getPrimarySelectedRowMulti();
                                    if (primarySelected && !primarySelected.classList.contains('bg-blue-100')) {
                                        subId = primarySelected.getAttribute('data-submission-id') || '';
                                        userId = primarySelected.getAttribute('data-user-id') || '';
                                        templateRow = primarySelected;
                                        insertAfter = primarySelected;
                                    }
                                    if (!insertAfter) {
                                        var lastDataRows = tableBody.querySelectorAll('tr.data-row');
                                        var matchSub = function(tr) {
                                            if (tr.classList.contains('bg-blue-100')) return false;
                                            if (tr.classList.contains('grand-total-row')) return false;
                                            if (tr.classList.contains('separator-row')) return false;
                                            var s = tr.getAttribute('data-submission-id');
                                            var u = tr.getAttribute('data-user-id');
                                            if (subId) return s === subId;
                                            if (userId) return u === userId;
                                            return true;
                                        };
                                        for (var i = lastDataRows.length - 1; i >= 0; i--) {
                                            if (matchSub(lastDataRows[i])) {
                                                templateRow = lastDataRows[i];
                                                break;
                                            }
                                        }
                                        insertAfter = null;
                                    }
                                    // Final fallback: use any non-blue, non-grand-total data row as template
                                    if (!templateRow) {
                                        var fallbackSepRows = tableBody.querySelectorAll('tr.data-row:not(.bg-blue-100):not(.grand-total-row):not(.kpi-finalize-total-row):not(.separator-row)');
                                        if (fallbackSepRows.length > 0) templateRow = fallbackSepRows[fallbackSepRows.length - 1];
                                    }
                                    if (!templateRow) return;
                                    var colCount = fields.length;
                                    function rowAfterSepHasBlueMultiLocal(sepTr) {
                                        var el = sepTr.nextElementSibling;
                                        while (el) {
                                            if (el.classList.contains('separator-row')) return false;
                                            if (el.classList.contains('grand-total-row') || el.classList.contains('kpi-finalize-total-row')) break;
                                            if (el.classList.contains('bg-blue-100')) return true;
                                            el = el.nextElementSibling;
                                        }
                                        return false;
                                    }
                                    function lastNonBlueDataAfterSepMultiLocal(sepTr) {
                                        var last = null;
                                        var el = sepTr.nextElementSibling;
                                        while (el) {
                                            if (el.classList.contains('separator-row')) break;
                                            if (el.classList.contains('grand-total-row') || el.classList.contains('kpi-finalize-total-row')) break;
                                            if (el.classList.contains('data-row') && !el.classList.contains('bg-blue-100')) last = el;
                                            el = el.nextElementSibling;
                                        }
                                        return last;
                                    }
                                    function appendEmptyBlueSummaryAfterMulti(anchorTr) {
                                        var nr = document.createElement('tr');
                                        nr.className = 'data-row bg-blue-100 group border-l-4 border-indigo-200';
                                        nr.setAttribute('data-submission-id', subId);
                                        nr.setAttribute('data-row-type', 'summary');
                                        if (userId) nr.setAttribute('data-user-id', userId);
                                        nr.setAttribute('data-row-uid', makeRowUid());
                                        var stickySlotByIndexSep = window.performanceStickySlotByIndex || {};
                                        var lastColSep = tableBody ? parseInt(tableBody.getAttribute('data-last-visible-col-index') || '-1', 10) : -1;
                                        if (lastColSep < 0) lastColSep = colCount - 1;
                                        for (var ci = 0; ci < colCount; ci++) {
                                            var cell = document.createElement('td');
                                            cell.setAttribute('data-field-col', String(ci));
                                            var field = fields && fields[ci] ? fields[ci] : null;
                                            var keyF = field ? getFieldKey(field) : '';
                                            var labelNormS = (keyF || '').toLowerCase().replace(/[^a-z0-9]+/g, '');
                                            var isNoColS = labelNormS === 'no';
                                            var isNumOrCalcS = (field && ((field.type || '') === 'number' || (field.meta && field.meta.calc)));
                                            var alignClassS = isNumOrCalcS && !isNoColS ? 'text-right' : (isNoColS ? 'text-center' : '');
                                            var stickyClassS = stickySlotByIndexSep[ci] != null ? ('sticky-perf sticky-perf-' + stickySlotByIndexSep[ci]) : '';
                                            cell.className = 'px-4 py-1.5 border-r border-gray-200 bg-blue-100 ' + alignClassS + ' ' + stickyClassS;
                                            if (ci === lastColSep) cell.classList.add('relative');
                                            var contentS;
                                            if (ci === 0) {
                                                var spanS = document.createElement('span');
                                                spanS.className = 'text-sm font-semibold text-gray-800';
                                                spanS.textContent = '';
                                                contentS = spanS;
                                            } else {
                                                var inpS = document.createElement('input');
                                                inpS.type = 'text';
                                                inpS.className = 'w-full text-sm text-gray-900 border-0 focus:ring-0 focus:outline-none bg-transparent font-semibold';
                                                inpS.value = '';
                                                if (keyF) inpS.setAttribute('name', keyF);
                                                contentS = inpS;
                                            }
                                            if (ci === lastColSep) {
                                                var wrapS = document.createElement('div');
                                                wrapS.className = 'flex items-center gap-2 relative pr-8 min-h-[28px]';
                                                wrapS.appendChild(contentS);
                                                var delBtnS = document.createElement('button');
                                                delBtnS.type = 'button';
                                                delBtnS.className = 'delete-row-btn absolute right-0 top-1/2 -translate-y-1/2 w-7 h-7 flex items-center justify-center rounded text-red-600 hover:bg-red-50 hover:text-red-800 opacity-0 group-hover:opacity-100 transition-opacity text-lg font-bold leading-none';
                                                delBtnS.title = 'Delete row';
                                                delBtnS.textContent = 'A';
                                                wrapS.appendChild(delBtnS);
                                                cell.appendChild(wrapS);
                                            } else {
                                                cell.appendChild(contentS);
                                            }
                                            nr.appendChild(cell);
                                        }
                                        anchorTr.insertAdjacentElement('afterend', nr);
                                        return nr;
                                    }
                                    function appendEmptyDataRowAfterMulti(anchorTr, templateDataRow) {
                                        if (!anchorTr || !templateDataRow) return null;
                                        var nr = document.createElement('tr');
                                        nr.className = 'data-row hover:bg-gray-50 group border-l-4 border-indigo-200';
                                        nr.setAttribute('data-submission-id', subId);
                                        if (userId) nr.setAttribute('data-user-id', userId);
                                        nr.setAttribute('data-row-uid', makeRowUid());
                                        var tds = window.getRowTdCells(templateDataRow);
                                        for (var ci = 0; ci < tds.length; ci++) {
                                            var cell = tds[ci].cloneNode(true);
                                            cell.setAttribute('data-field-col', String(ci));
                                            var inputs = cell.querySelectorAll('input, select, textarea');
                                            inputs.forEach(function(input) {
                                                if (input.tagName === 'SELECT') input.selectedIndex = 0; else input.value = '';
                                            });
                                            nr.appendChild(cell);
                                        }
                                        anchorTr.insertAdjacentElement('afterend', nr);
                                        return nr;
                                    }
                                    var separatorRow = document.createElement('tr');
                                    separatorRow.className = 'separator-row border-l-4 border-indigo-200';
                                    separatorRow.setAttribute('data-submission-id', subId);
                                    if (userId) separatorRow.setAttribute('data-user-id', userId);
                                    var sepCell = document.createElement('td');
                                    sepCell.setAttribute('colspan', colCount);
                                    sepCell.className = 'h-4 min-h-[1rem] px-4 py-2 bg-gray-200 border-t-2 border-b-2 border-gray-300';
                                    separatorRow.appendChild(sepCell);
                                    var newRow = null;
                                    var firstDataRow = null;
                                    if (insertAfter) {
                                        insertAfter.insertAdjacentElement('afterend', separatorRow);
                                        var immediateAfterSep = separatorRow.nextElementSibling;
                                        if (immediateAfterSep && immediateAfterSep.classList.contains('bg-blue-100')) {
                                            separatorRow.parentNode.insertBefore(immediateAfterSep, separatorRow);
                                        }
                                        ensureSectionHasBlueRow(insertAfter);
                                        firstDataRow = appendEmptyDataRowAfterMulti(separatorRow, templateRow);
                                        if (firstDataRow && !rowAfterSepHasBlueMultiLocal(separatorRow)) {
                                            newRow = appendEmptyBlueSummaryAfterMulti(firstDataRow);
                                        }
                                    } else {
                                        tableBody.appendChild(separatorRow);
                                        firstDataRow = appendEmptyDataRowAfterMulti(separatorRow, templateRow);
                                        if (firstDataRow && !rowAfterSepHasBlueMultiLocal(separatorRow)) {
                                            newRow = appendEmptyBlueSummaryAfterMulti(firstDataRow);
                                        }
                                    }
                                    if (typeof normalizeBlueRowDashes === 'function') normalizeBlueRowDashes();
                                    clearSelectionMulti();
                                    var focusRow = firstDataRow || newRow;
                                    if (!focusRow) {
                                        var scan = separatorRow.nextElementSibling;
                                        while (scan) {
                                            if (scan.classList.contains('separator-row')) break;
                                            if (scan.classList.contains('grand-total-row') || scan.classList.contains('kpi-finalize-total-row')) break;
                                            if (scan.classList.contains('data-row') && !scan.classList.contains('bg-blue-100')) { focusRow = scan; break; }
                                            if (scan.classList.contains('bg-blue-100') && !focusRow) { focusRow = scan; }
                                            scan = scan.nextElementSibling;
                                        }
                                    }
                                    if (focusRow) {
                                        var firstCell = focusRow.querySelector('td');
                                        if (firstCell && typeof setCellSelected === 'function') setCellSelected(focusRow, firstCell, true);
                                    }
                                    updateDeleteBtnMulti();
                                    scheduleAutoSave();
                                    setTimeout(function() {
                                        if (typeof window.performSaveTableData === 'function') window.performSaveTableData();
                                    }, 100);
                                }
                                function ensureSectionHasBlueRow(dataRow) {
                                    if (!dataRow || !tableBody || dataRow.classList.contains('bg-blue-100')) return;
                                    var subId = dataRow.getAttribute('data-submission-id') || '';
                                    var userId = dataRow.getAttribute('data-user-id') || '';
                                    var sectionRows = findSectionRowsContainingRow(dataRow);
                                    if (!sectionRows || sectionRows.length === 0) return;
                                    var hasBlue = sectionRows.some(function(tr) { return tr.classList.contains('bg-blue-100'); });
                                    if (hasBlue) return;
                                    var lastDataRow = null;
                                    for (var i = sectionRows.length - 1; i >= 0; i--) {
                                        if (!sectionRows[i].classList.contains('bg-blue-100') && sectionRows[i].classList.contains('data-row')) { lastDataRow = sectionRows[i]; break; }
                                    }
                                    if (!lastDataRow) return;
                                    var newBlue = document.createElement('tr');
                                    newBlue.className = 'data-row bg-blue-100 border-l-4 border-indigo-200';
                                    newBlue.setAttribute('data-submission-id', subId);
                                    newBlue.setAttribute('data-row-type', 'summary');
                                    newBlue.setAttribute('data-user-id', userId);
                                    var colCountEnsure = fields && fields.length > 0 ? fields.length : (window.getRowTdCells(lastDataRow).length || 1);
                                    for (var ci = 0; ci < colCountEnsure; ci++) {
                                        var cell = document.createElement('td');
                                        cell.setAttribute('data-field-col', String(ci));
                                        cell.className = 'px-4 py-1.5 border-r border-gray-200 bg-blue-100';
                                        var field = fields && fields[ci] ? fields[ci] : null;
                                        var keyF = field ? getFieldKey(field) : '';
                                        if (ci === 0) {
                                            var span = document.createElement('span');
                                            span.className = 'text-sm font-semibold text-gray-800';
                                            span.textContent = '';
                                            cell.appendChild(span);
                                        } else {
                                            var inp = document.createElement('input');
                                            inp.type = 'text';
                                            inp.className = 'w-full text-sm text-gray-900 border-0 focus:ring-0 focus:outline-none bg-transparent font-semibold';
                                            inp.value = '';
                                            if (keyF) inp.setAttribute('name', keyF);
                                            cell.appendChild(inp);
                                        }
                                        newBlue.appendChild(cell);
                                    }
                                    newBlue.setAttribute('data-submission-id', subId);
                                    newBlue.setAttribute('data-row-type', 'summary');
                                    newBlue.setAttribute('data-user-id', userId);
                                    lastDataRow.insertAdjacentElement('afterend', newBlue);
                                    if (typeof normalizeBlueRowDashes === 'function') normalizeBlueRowDashes();
                                }
                                function deleteLastRowMulti() {
                                    var rows = tableBody ? tableBody.querySelectorAll('tr.data-row') : [];
                                    if (rows.length > 0) {
                                        var lastRow = rows[rows.length - 1];
                                        var prev = lastRow.previousElementSibling;
                                        var next = lastRow.nextElementSibling;
                                        var lastWasBlue = lastRow.classList.contains('bg-blue-100');
                                        lastRow.remove();
                                        if (prev && prev.classList.contains('separator-row')) prev.remove();
                                        if (lastWasBlue && next && next.classList.contains('separator-row')) next.remove();
                                        updateDeleteBtnMulti();
                                        scheduleAutoSave();
                                    }
                                }
                                function updateDeleteBtnMulti() {
                                    var container = document.getElementById('delete-last-row-container-multi');
                                    var rows = tableBody ? tableBody.querySelectorAll('tr.data-row') : [];
                                    if (rows.length > 0 && container) {
                                        var lastRow = rows[rows.length - 1];
                                        var rect = lastRow.getBoundingClientRect();
                                        var containerRect = document.getElementById('table-container-multi').getBoundingClientRect();
                                        container.style.top = (rect.top - containerRect.top + rect.height / 2 - 12) + 'px';
                                    }
                                }

                                // Drag-select state (default drag to select range)
                                var dragStartCell = null;
                                var isDragSelecting = false;
                                var isDragAdditive = false;

                                // Excel-like auto-scroll when selecting near viewport edges
                                var selectionAutoScrollRaf = null;
                                var selectionAutoScrollDirection = 0; // -1 up, 0 none, 1 down
                                var SELECTION_SCROLL_ZONE = 60;
                                var SELECTION_SCROLL_SPEED = 12;
                                function getScrollableForTable() {
                                    var el = tableContainerMulti || tableBody;
                                    if (!el) return null;
                                    var custom = el.closest ? el.closest('[data-scroll-container]') : null;
                                    if (custom) return custom;
                                    el = tableContainerMulti || document.body;
                                    while (el && el !== document.body) {
                                        var style = el.currentStyle || (window.getComputedStyle && window.getComputedStyle(el));
                                        if (style) {
                                            var oy = (style.overflowY || '').toLowerCase();
                                            if ((oy === 'auto' || oy === 'scroll' || oy === 'overlay') && el.scrollHeight > el.clientHeight)
                                                return el;
                                        }
                                        el = el.parentElement;
                                    }
                                    return window;
                                }
                                function runSelectionAutoScroll() {
                                    if (!isDragSelecting || selectionAutoScrollDirection === 0) return;
                                    var scrollTarget = getScrollableForTable();
                                    var amount = SELECTION_SCROLL_SPEED * selectionAutoScrollDirection;
                                    if (scrollTarget === window) {
                                        window.scrollBy({ top: amount, behavior: 'auto' });
                                    } else if (scrollTarget && scrollTarget.scrollBy) {
                                        scrollTarget.scrollBy({ top: amount, behavior: 'auto' });
                                    } else if (scrollTarget) {
                                        scrollTarget.scrollTop = (scrollTarget.scrollTop || 0) + amount;
                                    }
                                    selectionAutoScrollRaf = requestAnimationFrame(runSelectionAutoScroll);
                                }
                                function updateSelectionAutoScroll(clientY) {
                                    var zone = SELECTION_SCROLL_ZONE;
                                    var viewHeight = window.innerHeight;
                                    var newDir = 0;
                                    if (clientY < zone) newDir = -1;
                                    else if (clientY > viewHeight - zone) newDir = 1;
                                    if (newDir !== selectionAutoScrollDirection) {
                                        selectionAutoScrollDirection = newDir;
                                        if (selectionAutoScrollRaf) {
                                            cancelAnimationFrame(selectionAutoScrollRaf);
                                            selectionAutoScrollRaf = null;
                                        }
                                        if (newDir !== 0) runSelectionAutoScroll();
                                    }
                                }
                                function stopSelectionAutoScroll() {
                                    selectionAutoScrollDirection = 0;
                                    if (selectionAutoScrollRaf) {
                                        cancelAnimationFrame(selectionAutoScrollRaf);
                                        selectionAutoScrollRaf = null;
                                    }
                                }
                                function getRowIndex(tr) {
                                    var rows = tableBody ? tableBody.querySelectorAll('tr.data-row') : [];
                                    return Array.prototype.indexOf.call(rows, tr);
                                }
                                function getColIndex(td) {
                                    if (!td) return -1;
                                    var attr = td.getAttribute && td.getAttribute('data-field-col');
                                    if (attr !== null && attr !== '') {
                                        var parsed = parseInt(attr, 10);
                                        if (!isNaN(parsed) && parsed >= 0) return parsed;
                                    }
                                    var tr = td.parentElement;
                                    if (!tr || String(tr.tagName).toUpperCase() !== 'TR') return -1;
                                    var cells = typeof window.getRowTdCells === 'function' ? window.getRowTdCells(tr) : [];
                                    return cells.indexOf(td);
                                }
                                function isPlainAggregatableDataRow(tr) {
                                    if (!tr || !tr.classList.contains('data-row')) return false;
                                    if (String(tr.getAttribute('data-manual-total-row') || '') === '1') return false;
                                    if (tr.classList.contains('bg-blue-100')) return false;
                                    if (tr.classList.contains('grand-total-row')) return false;
                                    if (tr.classList.contains('kpi-finalize-total-row')) return false;
                                    if (tr.id === 'grand-total-row-template' || tr.id === 'kpi-finalize-total-row-template') return false;
                                    return true;
                                }
                                function rowMatchesSectionKeys(r, subId, userId) {
                                    if (!r) return false;
                                    return (r.getAttribute('data-submission-id') || '') === subId
                                        && (r.getAttribute('data-user-id') || '') === userId;
                                }
                                /** New plain data rows after a blue summary start a separate block (same coordinator can have multiple tables without a separator). */
                                function isAggregatableWhiteDataRowForSectionBoundary(r) {
                                    return !!(r && isPlainAggregatableDataRow(r));
                                }
                                /**
                                 * Split tbody into logical blocks. Rows with the same submission/user are NOT always one section:
                                 * stop including rows when keys change, and end a section after a blue summary row when more aggregatable data follows.
                                 */
                                function buildTableBodySections() {
                                    if (!tableBody) return [];
                                    var allTrs = Array.prototype.slice.call(tableBody.children);
                                    var sections = [];
                                    var i = 0;
                                    while (i < allTrs.length) {
                                        var tr = allTrs[i];
                                        if (tr.id === 'manual-total-empty-row-template'
                                            || tr.id === 'add-grand-total-row'
                                            || tr.id === 'grand-total-row-template'
                                            || tr.id === 'kpi-finalize-total-row-template') {
                                            i++;
                                            continue;
                                        }
                                        if (tr.classList && (tr.classList.contains('grand-total-row') || tr.classList.contains('kpi-finalize-total-row'))) {
                                            i++;
                                            continue;
                                        }
                                        if (String(tr.getAttribute('data-manual-total-row') || '') === '1') {
                                            i++;
                                            continue;
                                        }
                                        if (tr.classList.contains('section-header-row') || tr.classList.contains('separator-row')) {
                                            i++;
                                            continue;
                                        }
                                        var subId = tr.getAttribute('data-submission-id') || '';
                                        var userId = tr.getAttribute('data-user-id') || '';
                                        var sectionRows = [];
                                        while (i < allTrs.length) {
                                            var r = allTrs[i];
                                            if (r.classList.contains('section-header-row')) break;
                                            if (r.classList.contains('separator-row')) {
                                                i++;
                                                break;
                                            }
                                            if (!rowMatchesSectionKeys(r, subId, userId)) break;
                                            sectionRows.push(r);
                                            if (r.classList.contains('bg-blue-100')) {
                                                var nextR = allTrs[i + 1];
                                                if (nextR
                                                    && !nextR.classList.contains('section-header-row')
                                                    && !nextR.classList.contains('separator-row')
                                                    && rowMatchesSectionKeys(nextR, subId, userId)
                                                    && isAggregatableWhiteDataRowForSectionBoundary(nextR)) {
                                                    i++;
                                                    break;
                                                }
                                            }
                                            i++;
                                        }
                                        if (sectionRows.length > 0) sections.push(sectionRows);
                                    }
                                    return sections;
                                }
                                function findSectionRowsContainingRow(tr) {
                                    if (!tr) return null;
                                    var sections = buildTableBodySections();
                                    for (var s = 0; s < sections.length; s++) {
                                        if (sections[s].indexOf(tr) >= 0) return sections[s];
                                    }
                                    return null;
                                }
                                function hasMultipleSectionsForKey(subId, userId) {
                                    if (!tableBody) return false;
                                    var sections = buildTableBodySections();
                                    var count = 0;
                                    for (var i = 0; i < sections.length; i++) {
                                        var sec = sections[i];
                                        if (!sec || sec.length === 0) continue;
                                        if (rowMatchesSectionKeys(sec[0], subId, userId)) {
                                            count++;
                                            if (count > 1) return true;
                                        }
                                    }
                                    return false;
                                }
                                function getOrderedPasteableDataRows() {
                                    if (!tableBody) return [];
                                    return Array.prototype.filter.call(tableBody.querySelectorAll('tr.data-row'), isPlainAggregatableDataRow);
                                }
                                function sortSelectedTdsRowMajor(nodes) {
                                    var rowsOrder = getOrderedPasteableDataRows();
                                    function rowOrderIndex(tr) {
                                        var i = rowsOrder.indexOf(tr);
                                        return i >= 0 ? i : 999999;
                                    }
                                    var arr = Array.prototype.slice.call(nodes);
                                    arr.sort(function(a, b) {
                                        var tra = a.closest('tr.data-row');
                                        var trb = b.closest('tr.data-row');
                                        var ia = rowOrderIndex(tra);
                                        var ib = rowOrderIndex(trb);
                                        if (ia !== ib) return ia - ib;
                                        return getColIndex(a) - getColIndex(b);
                                    });
                                    return arr;
                                }
                                function groupTdsIntoRows(sortedTds) {
                                    var groups = [];
                                    var cur = [];
                                    var curTr = null;
                                    sortedTds.forEach(function(td) {
                                        var tr = td.closest('tr.data-row');
                                        if (!tr || tr.classList.contains('bg-blue-100') || tr.classList.contains('grand-total-row') || String(tr.getAttribute('data-manual-total-row') || '') === '1') return;
                                        if (tr !== curTr) {
                                            if (cur.length) groups.push(cur);
                                            cur = [];
                                            curTr = tr;
                                        }
                                        cur.push(td);
                                    });
                                    if (cur.length) groups.push(cur);
                                    return groups;
                                }
                                function buildClipboardRowsFromSelection(sourceTdsArray) {
                                    var sorted = sortSelectedTdsRowMajor(sourceTdsArray);
                                    var groups = groupTdsIntoRows(sorted);
                                    return groups.map(function(rowTds) {
                                        return rowTds.map(function(td) {
                                            var read = readValueFromTd(td);
                                            return read ? String(read.value) : '';
                                        });
                                    });
                                }
                                function parsePlainTextToValueGrid(text) {
                                    if (text == null || String(text) === '') return null;
                                    var rows = String(text).replace(/\r\n/g, '\n').replace(/\r/g, '\n').split('\n');
                                    while (rows.length && rows[rows.length - 1] === '') rows.pop();
                                    if (!rows.length) return null;
                                    return rows.map(function(line) { return line.split('\t'); });
                                }
                                function isProbablyValueGrid(grid) {
                                    if (!grid || !grid.length) return false;
                                    if (grid.length > 1) return true;
                                    return grid[0].length > 1;
                                }
                                function getPasteAnchorTd() {
                                    var active = document.activeElement;
                                    if (active && tableContainerMulti && tableContainerMulti.contains(active)) {
                                        var tdA = active.closest && active.closest('td');
                                        if (tdA && tableBody && tableBody.contains(tdA)) return tdA;
                                    }
                                    if (tableBody) {
                                        var sel = tableBody.querySelectorAll('td.cell-selected');
                                        if (sel && sel.length) {
                                            var sorted = sortSelectedTdsRowMajor(sel);
                                            if (sorted[0]) return sorted[0];
                                        }
                                    }
                                    return lastClickedCellMulti;
                                }
                                function pasteValueGridFromAnchor(anchorTd, valueGrid) {
                                    if (!anchorTd || !valueGrid || !valueGrid.length || !tableBody) return 0;
                                    var anchorTr = anchorTd.closest('tr.data-row');
                                    if (!anchorTr || anchorTr.classList.contains('bg-blue-100') || anchorTr.classList.contains('grand-total-row')) return 0;
                                    var dataRows = getOrderedPasteableDataRows();
                                    var anchorRowIdx = dataRows.indexOf(anchorTr);
                                    if (anchorRowIdx < 0) return 0;
                                    var anchorColIdx = getColIndex(anchorTd);
                                    var applied = 0;
                                    for (var ri = 0; ri < valueGrid.length; ri++) {
                                        var srcRow = valueGrid[ri];
                                        if (!srcRow) continue;
                                        var tr = dataRows[anchorRowIdx + ri];
                                        if (!tr) break;
                                        var tds = window.getRowTdCells(tr);
                                        for (var cj = 0; cj < srcRow.length; cj++) {
                                            var v = srcRow[cj];
                                            v = (v === null || v === undefined) ? '' : String(v);
                                            var colIdx = anchorColIdx + cj;
                                            if (colIdx < 0 || colIdx >= tds.length) continue;
                                            var td = tds[colIdx];
                                            if (!td) continue;
                                            var readT = readValueFromTd(td);
                                            if (!readT) continue;
                                            var inpT = getEditableInputElFromTd(td);
                                            if (!inpT || inpT.readOnly || inpT.disabled) continue;
                                            if (writeValueToTd(td, readT.fieldKey, v)) {
                                                var aEl = td.querySelector('a[href]');
                                                if (aEl && v) aEl.setAttribute('href', v);
                                                else if (aEl && !v) aEl.removeAttribute('href');
                                                applied++;
                                            }
                                        }
                                    }
                                    if (applied > 0 && typeof scheduleRecomputeFormulas === 'function') scheduleRecomputeFormulas(anchorTd);
                                    return applied;
                                }
                                function getPasteableSelectedTdsSorted() {
                                    if (!tableBody) return [];
                                    var sel = tableBody.querySelectorAll('td.cell-selected');
                                    var arr = Array.prototype.filter.call(sel, function(td) {
                                        if (!td) return false;
                                        var tr = td.closest('tr.data-row');
                                        if (!tr) return false;
                                        if (tr.classList.contains('bg-blue-100') || tr.classList.contains('grand-total-row') || tr.classList.contains('kpi-finalize-total-row')) return false;
                                        var inp = getEditableInputElFromTd(td);
                                        return !!(inp && !inp.readOnly && !inp.disabled);
                                    });
                                    return sortSelectedTdsRowMajor(arr);
                                }
                                function flattenValueGridForPaste(grid) {
                                    var out = [];
                                    if (!grid || !grid.length) return out;
                                    for (var ri = 0; ri < grid.length; ri++) {
                                        var row = grid[ri] || [];
                                        for (var cj = 0; cj < row.length; cj++) {
                                            var cell = row[cj];
                                            out.push(cell === null || cell === undefined ? '' : String(cell));
                                        }
                                    }
                                    return out;
                                }
                                function pasteValueGridOntoMultiSelection(valueGrid) {
                                    if (!tableBody || !valueGrid || !valueGrid.length) return 0;
                                    var targets = getPasteableSelectedTdsSorted();
                                    if (targets.length < 2) return 0;
                                    var flat = flattenValueGridForPaste(valueGrid);
                                    if (!flat.length) return 0;
                                    var applied = 0;
                                    var n = Math.min(flat.length, targets.length);
                                    for (var i = 0; i < n; i++) {
                                        var v = flat[i];
                                        var td = targets[i];
                                        var read = readValueFromTd(td);
                                        if (!read) continue;
                                        if (writeValueToTd(td, read.fieldKey, v)) {
                                            var aEl = td.querySelector('a[href]');
                                            if (aEl && v) aEl.setAttribute('href', v);
                                            else if (aEl && !v) aEl.removeAttribute('href');
                                            applied++;
                                        }
                                    }
                                    if (applied > 0 && typeof scheduleRecomputeFormulas === 'function') scheduleRecomputeFormulas(targets[0]);
                                    return applied;
                                }
                                function pastePlainTextToMultiSelection(text) {
                                    var targets = getPasteableSelectedTdsSorted();
                                    if (targets.length < 2) return 0;
                                    var raw = String(text || '').replace(/\r\n/g, '\n').replace(/\r/g, '\n');
                                    var lines = raw.split('\n');
                                    while (lines.length && lines[lines.length - 1] === '') lines.pop();
                                    if (!lines.length) return 0;
                                    var applied = 0;
                                    var n = targets.length;
                                    for (var i = 0; i < n; i++) {
                                        var v = lines.length === 1 ? lines[0] : (lines[i] !== undefined ? lines[i] : '');
                                        var td = targets[i];
                                        var read = readValueFromTd(td);
                                        if (!read) continue;
                                        if (writeValueToTd(td, read.fieldKey, v)) {
                                            var aEl = td.querySelector('a[href]');
                                            if (aEl && v) aEl.setAttribute('href', v);
                                            else if (aEl && !v) aEl.removeAttribute('href');
                                            applied++;
                                        }
                                    }
                                    if (applied > 0 && typeof scheduleRecomputeFormulas === 'function') scheduleRecomputeFormulas(targets[0]);
                                    return applied;
                                }
                                function pasteSingleValueToMatchingColumn(val, fieldKey) {
                                    if (!tableBody) return 0;
                                    var selectedTds = tableBody.querySelectorAll('td.cell-selected');
                                    var list = selectedTds && selectedTds.length ? Array.prototype.slice.call(selectedTds) : (lastClickedCellMulti ? [lastClickedCellMulti] : []);
                                    if (!list.length) return 0;
                                    var fk = String(fieldKey || '');
                                    var applied = 0;
                                    list.forEach(function(td) {
                                        if (!td) return;
                                        var tr = td.closest('tr.data-row');
                                        if (!tr) return;
                                        if (tr.classList.contains('bg-blue-100') || tr.classList.contains('grand-total-row') || tr.classList.contains('kpi-finalize-total-row') || String(tr.getAttribute('data-manual-total-row') || '') === '1') return;
                                        var inp = getEditableInputElFromTd(td);
                                        if (!inp || inp.readOnly || inp.disabled) return;
                                        var read = readValueFromTd(td);
                                        if (!read) return;
                                        if (String(read.fieldKey) !== fk) return;
                                        if (writeValueToTd(td, fk, String(val))) {
                                            var aEl = td.querySelector('a[href]');
                                            if (aEl && val) aEl.setAttribute('href', String(val));
                                            else if (aEl && !val) aEl.removeAttribute('href');
                                            applied++;
                                        }
                                    });
                                    if (applied > 0 && typeof scheduleRecomputeFormulas === 'function') {
                                        var t0 = list[0];
                                        if (t0) scheduleRecomputeFormulas(t0);
                                    }
                                    return applied;
                                }
                                function getFieldIndexByKey(fieldKey) {
                                    var key = String(fieldKey || '');
                                    for (var i = 0; i < fields.length; i++) {
                                        if (getFieldKey(fields[i]) === key) return i;
                                    }
                                    return -1;
                                }
                                function normalizeMetricTokenForMatch(v) {
                                    return String(v || '').toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
                                }
                                /**
                                 * Resolve template field key a column index. Must not return the first of several matches
                                 * (e.g. many sub-columns share label "M"/"F") or blue-row sums and same-row formulas land in wrong cells.
                                 */
                                function getFieldIndexByKeyFlexible(fieldKey) {
                                    var exact = getFieldIndexByKey(fieldKey);
                                    if (exact >= 0) return exact;
                                    var raw = String(fieldKey || '').trim();
                                    if (!raw) return -1;
                                    var targetNorm = normalizeMetricTokenForMatch(raw);
                                    if (!targetNorm) return -1;
                                    var hitsKey = [];
                                    var hitsBoth = [];
                                    var hitsLabel = [];
                                    var i;
                                    for (i = 0; i < fields.length; i++) {
                                        var f = fields[i] || {};
                                        var k = getFieldKey(f);
                                        var keyNorm = normalizeMetricTokenForMatch(k);
                                        var labelNorm = normalizeMetricTokenForMatch(f.label || '');
                                        var both = normalizeMetricTokenForMatch((k || '') + '_' + (f.label || ''));
                                        if (keyNorm === targetNorm) hitsKey.push(i);
                                        if (both === targetNorm) hitsBoth.push(i);
                                        if (labelNorm === targetNorm) hitsLabel.push(i);
                                    }
                                    if (hitsKey.length === 1) return hitsKey[0];
                                    if (hitsBoth.length === 1) return hitsBoth[0];
                                    if (hitsLabel.length === 1) return hitsLabel[0];
                                    return -1;
                                }
                                function getSectionRowsContextFromRow(anchorRow) {
                                    if (!tableBody || !anchorRow) return null;
                                    var sectionRows = findSectionRowsContainingRow(anchorRow);
                                    if (!sectionRows || sectionRows.length === 0) return null;
                                    var dataRows = sectionRows.filter(function(tr) { return tr.classList.contains('data-row') && !tr.classList.contains('bg-blue-100'); });
                                    return { sectionRows: sectionRows, dataRows: dataRows };
                                }
                                function buildLegacySectionRefFromRow(row) {
                                    if (!row) return '';
                                    var subId = String(row.getAttribute('data-submission-id') || '');
                                    var userId = String(row.getAttribute('data-user-id') || '');
                                    return 'sub:' + subId + '|user:' + userId;
                                }
                                function getSectionOrdinalForRow(row) {
                                    if (!row || typeof buildTableBodySections !== 'function') return 1;
                                    var subId = String(row.getAttribute('data-submission-id') || '');
                                    var userId = String(row.getAttribute('data-user-id') || '');
                                    var sections = buildTableBodySections();
                                    var ordinal = 0;
                                    for (var i = 0; i < sections.length; i++) {
                                        var sec = sections[i];
                                        if (!sec || sec.length === 0) continue;
                                        var first = sec[0];
                                        if (!rowMatchesSectionKeys(first, subId, userId)) continue;
                                        ordinal++;
                                        if (sec.indexOf(row) >= 0) return ordinal;
                                    }
                                    return Math.max(1, ordinal);
                                }
                                function buildSectionRefFromRow(row) {
                                    if (!row) return '';
                                    var legacy = buildLegacySectionRefFromRow(row);
                                    var ord = getSectionOrdinalForRow(row);
                                    return legacy + '|sec:' + String(ord);
                                }
                                function buildSectionRowUidSetFromBlueRow(blueRow) {
                                    var uidSet = {};
                                    if (!blueRow || typeof findSectionRowsContainingRow !== 'function') return uidSet;
                                    var sec = findSectionRowsContainingRow(blueRow) || [];
                                    sec.forEach(function(tr) {
                                        if (!isPlainAggregatableDataRow(tr)) return;
                                        var uid = (tr.getAttribute('data-row-uid') || '').trim();
                                        if (uid) uidSet[uid] = true;
                                    });
                                    return uidSet;
                                }
                                function mappingMatchesSectionRows(mapping, sectionUidSet) {
                                    mapping = mapping || {};
                                    sectionUidSet = sectionUidSet || {};
                                    var keys = Object.keys(sectionUidSet);
                                    if (keys.length === 0) return true;
                                    var rowUids = Array.isArray(mapping.row_uids) ? mapping.row_uids : [];
                                    if (rowUids.length === 0) return true;
                                    for (var i = 0; i < rowUids.length; i++) {
                                        var uid = String(rowUids[i] || '').trim();
                                        if (uid && sectionUidSet[uid]) return true;
                                    }
                                    return false;
                                }
                                /** When DOM/cell attrs lack row scope, copy from template summary_cell_mappings (saved via summary API). */
                                function enrichMappingWithTemplateRowScope(blueTd, blueRow, mapping) {
                                    mapping = mapping || {};
                                    var hasScope = (Array.isArray(mapping.row_uids) && mapping.row_uids.length > 0)
                                        || (Array.isArray(mapping.row_indices) && mapping.row_indices.length > 0);
                                    if (hasScope || !blueTd || !blueRow) return mapping;
                                    var colIdx = getColIndex(blueTd);
                                    if (colIdx < 0 || colIdx >= fields.length) return mapping;
                                    var targetField = getFieldKey(fields[colIdx]);
                                    var sectionRef = String(mapping.section_ref || '').trim() || buildSectionRefFromRow(blueRow);
                                    var legacySectionRef = buildLegacySectionRefFromRow(blueRow);
                                    var subId = String(blueRow.getAttribute('data-submission-id') || '');
                                    var userId = String(blueRow.getAttribute('data-user-id') || '');
                                    var allowLegacySectionRef = !hasMultipleSectionsForKey(subId, userId);
                                    var sectionUidSet = buildSectionRowUidSetFromBlueRow(blueRow);
                                    if (!Array.isArray(summaryCellMappingsData)) return mapping;
                                    for (var ti = 0; ti < summaryCellMappingsData.length; ti++) {
                                        var m = summaryCellMappingsData[ti] || {};
                                        if (String(m.target_field || '') !== String(targetField)) continue;
                                        var mRef = String(m.section_ref || '').trim();
                                        if (sectionRef) {
                                            if (mRef !== String(sectionRef).trim()) {
                                                if (!(allowLegacySectionRef && mRef === legacySectionRef)) continue;
                                            }
                                        }
                                        if (!mappingMatchesSectionRows(m, sectionUidSet)) continue;
                                        var merged = Object.assign({}, mapping);
                                        if (Array.isArray(m.row_uids) && m.row_uids.length > 0) merged.row_uids = m.row_uids.slice();
                                        if (Array.isArray(m.row_indices) && m.row_indices.length > 0) {
                                            merged.row_indices = m.row_indices.map(function(x) { return parseInt(String(x), 10); }).filter(function(n) { return !isNaN(n); });
                                        }
                                        if ((merged.row_uids && merged.row_uids.length) || (merged.row_indices && merged.row_indices.length)) {
                                            setCellFormulaMapping(blueTd, merged);
                                            return merged;
                                        }
                                    }
                                    return mapping;
                                }
                                function findSummaryOutputByTarget(targetFieldKey, blueRow) {
                                    var rowSectionRef = buildSectionRefFromRow(blueRow);
                                    var rowLegacySectionRef = buildLegacySectionRefFromRow(blueRow);
                                    var rowSubId = String((blueRow && blueRow.getAttribute('data-submission-id')) || '');
                                    var rowUserId = String((blueRow && blueRow.getAttribute('data-user-id')) || '');
                                    var allowLegacySectionRef = !hasMultipleSectionsForKey(rowSubId, rowUserId);
                                    var sectionUidSet = buildSectionRowUidSetFromBlueRow(blueRow);
                                    var directCellMapping = getCellFormulaMapping(blueRow ? window.getRowTdCells(blueRow)[getFieldIndexByKeyFlexible(targetFieldKey)] : null);
                                    if (directCellMapping) {
                                        return directCellMapping;
                                    }
                                    var cachedKey = buildSelectionMappingKey(rowSectionRef, targetFieldKey);
                                    if (summarySelectionMappingsByKey[cachedKey]) {
                                        return summarySelectionMappingsByKey[cachedKey];
                                    }
                                    var legacyCachedKey = buildSelectionMappingKey(rowLegacySectionRef, targetFieldKey);
                                    if (allowLegacySectionRef && summarySelectionMappingsByKey[legacyCachedKey]) {
                                        return summarySelectionMappingsByKey[legacyCachedKey];
                                    }
                                    if (!Array.isArray(summaryCellMappingsData) || summaryCellMappingsData.length === 0) return null;
                                    var exactMatches = [];
                                    summaryCellMappingsData.forEach(function(mapping, index) {
                                        mapping = mapping || {};
                                        if (String(mapping.target_field || '') !== String(targetFieldKey || '')) return;
                                        var mRef = String(mapping.section_ref || '');
                                        if (rowSectionRef) {
                                            if (mRef !== rowSectionRef) {
                                                if (!(allowLegacySectionRef && mRef === rowLegacySectionRef)) return;
                                            }
                                        }
                                        if (!mappingMatchesSectionRows(mapping, sectionUidSet)) return;
                                        exactMatches.push({ mapping: mapping, index: index });
                                    });
                                    if (exactMatches.length === 0) return null;
                                    exactMatches.sort(function(a, b) {
                                        var aCols = Array.isArray(a.mapping.source_columns) ? a.mapping.source_columns.length : 0;
                                        var bCols = Array.isArray(b.mapping.source_columns) ? b.mapping.source_columns.length : 0;
                                        if (aCols !== bCols) return bCols - aCols;
                                        var aHasB = String(a.mapping.sourceB || '').trim() !== '' ? 1 : 0;
                                        var bHasB = String(b.mapping.sourceB || '').trim() !== '' ? 1 : 0;
                                        if (aHasB !== bHasB) return bHasB - aHasB;
                                        return b.index - a.index;
                                    });
                                    return exactMatches[0].mapping;
                                }
                                function enrichMappingWithSavedMeta(mapping, targetFieldKey, blueRow) {
                                    mapping = mapping || {};
                                    var needsMeta = !mapping.ui_calc_type || !mapping.operation;
                                    if (!needsMeta) return mapping;
                                    var rowSectionRef = buildSectionRefFromRow(blueRow);
                                    var rowLegacySectionRef = buildLegacySectionRefFromRow(blueRow);
                                    var rowSubId = String((blueRow && blueRow.getAttribute('data-submission-id')) || '');
                                    var rowUserId = String((blueRow && blueRow.getAttribute('data-user-id')) || '');
                                    var allowLegacySectionRef = !hasMultipleSectionsForKey(rowSubId, rowUserId);
                                    var sectionUidSet = buildSectionRowUidSetFromBlueRow(blueRow);
                                    var candidates = [];
                                    if (Array.isArray(summaryCellMappingsData)) {
                                        summaryCellMappingsData.forEach(function(item, index) {
                                            item = item || {};
                                            if (String(item.target_field || '') !== String(targetFieldKey || '')) return;
                                            var mRef = String(item.section_ref || '');
                                            if (rowSectionRef) {
                                                if (mRef !== rowSectionRef) {
                                                    if (!(allowLegacySectionRef && mRef === rowLegacySectionRef)) return;
                                                }
                                            }
                                            if (!mappingMatchesSectionRows(item, sectionUidSet)) return;
                                            candidates.push({ item: item, score: 100 + index * 0.01 });
                                        });
                                    }
                                    if (Array.isArray(summaryRulesData)) {
                                        summaryRulesData.forEach(function(rule, ruleIdx) {
                                            var outputs = Array.isArray(rule && rule.outputs) ? rule.outputs : [];
                                            outputs.forEach(function(item, outIdx) {
                                                item = item || {};
                                                if (String(item.target_field || '') !== String(targetFieldKey || '')) return;
                                                var mRef = String(item.section_ref || '');
                                                if (rowSectionRef && (mRef === rowSectionRef || (allowLegacySectionRef && mRef === rowLegacySectionRef))) {
                                                    if (!mappingMatchesSectionRows(item, sectionUidSet)) return;
                                                    candidates.push({ item: item, score: 50 + ruleIdx + (outIdx * 0.01) });
                                                }
                                            });
                                        });
                                    }
                                    if (candidates.length === 0) return mapping;
                                    candidates.sort(function(a, b) { return b.score - a.score; });
                                    var meta = candidates[0].item || {};
                                    var merged = Object.assign({}, meta, mapping);
                                    if (!mapping.ui_calc_type && meta.ui_calc_type) merged.ui_calc_type = meta.ui_calc_type;
                                    if (!mapping.ui_formula_operation && meta.ui_formula_operation) merged.ui_formula_operation = meta.ui_formula_operation;
                                    if (!mapping.operation && meta.operation) merged.operation = meta.operation;
                                    return merged;
                                }
                                function addBlueTargetToCurrentSelection(blueTd) {
                                    if (!blueTd) return false;
                                    var blueRow = blueTd.closest('tr.data-row');
                                    if (!blueRow || !blueRow.classList.contains('bg-blue-100')) return false;
                                    var selected = tableBody ? Array.prototype.slice.call(tableBody.querySelectorAll('td.cell-selected')) : [];
                                    selected.forEach(function(td) {
                                        var tr = td.closest('tr.data-row');
                                        if (tr && tr.classList.contains('bg-blue-100') && td !== blueTd) {
                                            setCellSelected(tr, td, false);
                                        }
                                    });
                                    setCellSelected(blueRow, blueTd, true);
                                    lastClickedRowMulti = blueRow;
                                    lastClickedCellMulti = blueTd;
                                    setSelectionModeState('manual');
                                    updateFormulaButtonState();
                                    return true;
                                }
                                function autoSelectSourcesForBlueCell(blueTd, options) {
                                    options = options || {};
                                    if (!blueTd || !tableBody) return false;
                                    // Always re-read mapping from data-* attributes. The in-memory cache can drift
                                    // (e.g. shared merges, in-place edits) so switching blue cell A -> B -> A could
                                    // leave row_uids empty in cache while the DOM still has the saved scope — then
                                    // source row filtering matches nothing and white sources disappear.
                                    try { delete blueTd._formulaMapping; } catch (eFm) {}
                                    var blueRow = blueTd.closest('tr.data-row');
                                    if (!blueRow) return false;
                                    if (blueRow.classList.contains('grand-total-row')) {
                                        activeGrandTotalCell = blueTd;
                                        var targetColIndex = getColIndex(blueTd);
                                        if (targetColIndex < 0 || targetColIndex >= fields.length) return false;
                                        applyGrandTotalQuarterLabel(blueTd);
                                        var existingGtMap = getCellFormulaMapping(blueTd);
                                        if (existingGtMap) {
                                            restoreCalcTypeFromMapping(existingGtMap);
                                        }
                                        // Auto-suggest only when there is no saved action yet.
                                        if (!existingGtMap || !String(existingGtMap.ui_formula_operation || '').trim()) {
                                            maybeAutoSelectGrandTotalAction(blueTd);
                                        }
                                        syncGrandTotalQuarterCellValue(blueTd);
                                        clearSelectionMulti({ skipPopoverShell: true });
                                        setCellSelected(blueRow, blueTd, true);
                                        var mapGtClick = getCellFormulaMapping(blueTd);
                                        var persistedManualQuarter = mapGtClick && String(mapGtClick.source_quarter || '').trim() === 'manual';
                                        if (isGrandTotalManualSelectionMode(blueTd)) {
                                            if (persistedManualQuarter && Array.isArray(mapGtClick.row_uids) && mapGtClick.row_uids.length > 0) {
                                                var uidSetReselect = {};
                                                mapGtClick.row_uids.forEach(function(u) {
                                                    if (u != null && String(u).trim() !== '') uidSetReselect[String(u)] = true;
                                                });
                                                var allDrReselect = tableBody.querySelectorAll('tr.data-row');
                                                for (var ri = 0; ri < allDrReselect.length; ri++) {
                                                    var trR = allDrReselect[ri];
                                                    if (trR.classList.contains('bg-blue-100') || trR.classList.contains('grand-total-row')) continue;
                                                    var ruR = String(trR.getAttribute('data-row-uid') || '');
                                                    if (!uidSetReselect[ruR]) continue;
                                                    var tdsR = window.getRowTdCells(trR);
                                                    var srcR = tdsR[targetColIndex];
                                                    if (srcR) setCellSelected(trR, srcR, true);
                                                }
                                                lastClickedRowMulti = blueRow;
                                                lastClickedCellMulti = blueTd;
                                                setSelectionModeState('Using your current manual selection.');
                                                updateFormulaButtonState();
                                                return true;
                                            }
                                            if (persistedManualQuarter) {
                                                lastClickedRowMulti = blueRow;
                                                lastClickedCellMulti = blueTd;
                                                setSelectionModeState('Using your current manual selection.');
                                                updateFormulaButtonState();
                                                return true;
                                            }
                                            setGrandTotalManualOverride(blueTd, false);
                                        }
                                        var blueSyKeySel = typeof getGrandTotalSchoolYearBlueScopeKey === 'function' ? getGrandTotalSchoolYearBlueScopeKey(blueTd) : null;
                                        if (blueSyKeySel) {
                                            setGrandTotalManualOverride(blueTd, false);
                                            var bluePick = typeof findGrandTotalSchoolYearScopedBlueSourceCells === 'function' ? findGrandTotalSchoolYearScopedBlueSourceCells(blueTd, blueSyKeySel) : [];
                                            if (bluePick.length === 0) {
                                                lastClickedRowMulti = blueRow;
                                                lastClickedCellMulti = blueTd;
                                                setSelectionModeState('Grand total: no blue summary rows matched this scope in this column. Check semester / school year labels in each section.');
                                                if (!options.silent && typeof window.showToast === 'function') {
                                                    window.showToast('notice', 'No blue summary cells found for this scope in this column.');
                                                }
                                                updateFormulaButtonState();
                                                return true;
                                            }
                                            bluePick.forEach(function(tdB) {
                                                var trB = tdB.closest('tr.data-row');
                                                if (trB && tdB) setCellSelected(trB, tdB, true);
                                            });
                                            lastClickedRowMulti = blueRow;
                                            lastClickedCellMulti = blueTd;
                                            setSelectionModeState('Grand total: blue summary cells in this column for the selected scope. Ctrl+click blue cells to adjust.');
                                            updateFormulaButtonState();
                                            return true;
                                        }
                                        if (typeof isGrandTotalWizardCalculationAllColumnMode === 'function' && isGrandTotalWizardCalculationAllColumnMode(blueTd)) {
                                            setGrandTotalManualOverride(blueTd, false);
                                            var allRowsCalcSel = tableBody.querySelectorAll('tr.data-row');
                                            allRowsCalcSel.forEach(function(trC) {
                                                if (trC.classList.contains('bg-blue-100') || trC.classList.contains('grand-total-row')) return;
                                                var tdsC = window.getRowTdCells(trC);
                                                var srcC = tdsC[targetColIndex];
                                                if (srcC) setCellSelected(trC, srcC, true);
                                            });
                                            lastClickedRowMulti = blueRow;
                                            lastClickedCellMulti = blueTd;
                                            setSelectionModeState('Grand total: all plain data rows in this column. Ctrl+click rows to switch to manual.');
                                            updateFormulaButtonState();
                                            return true;
                                        }
                                        var wizGtCalc = document.getElementById('grand-total-cascade-wizard');
                                        var twGtCalc = document.getElementById('gt-wizard-type');
                                        var s2GtCalc = document.getElementById('gt-wizard-step2');
                                        if (wizGtCalc && !wizGtCalc.classList.contains('hidden') && twGtCalc && twGtCalc.value === 'calculation' && (!s2GtCalc || !String(s2GtCalc.value || '').trim())) {
                                            lastClickedRowMulti = blueRow;
                                            lastClickedCellMulti = blueTd;
                                            setSelectionModeState('Grand total: choose calculation in step 2, then Apply.');
                                            updateFormulaButtonState();
                                            return true;
                                        }
                                        var quarter = resolveGrandTotalQuarter(targetColIndex, blueTd);
                                        if (!quarter) {
                                            lastClickedRowMulti = blueRow;
                                            lastClickedCellMulti = blueTd;
                                            setSelectionModeState('Grand total: set quarter in grand total row (e.g., 1st Q), then rows will auto-select.');
                                            if (!options.silent && typeof window.showToast === 'function') {
                                                window.showToast('notice', 'Set quarter in the grand total quarter cell first (e.g., 1st Q).');
                                            }
                                            updateFormulaButtonState();
                                            return true;
                                        }
                                        setGrandTotalManualOverride(blueTd, false);
                                        var quarterColIdx = getQuarterColumnIndex();
                                        var allDataRows = tableBody.querySelectorAll('tr.data-row');
                                        allDataRows.forEach(function(tr) {
                                            if (tr.classList.contains('bg-blue-100') || tr.classList.contains('grand-total-row')) return;
                                            var tds = window.getRowTdCells(tr);
                                            var rowQuarter = null;
                                            if (quarterColIdx >= 0 && quarterColIdx < tds.length) {
                                                rowQuarter = detectQuarterFromTextValue(getCellRawValue(tds[quarterColIdx]));
                                            }
                                            if (!rowQuarter) rowQuarter = detectQuarterFromRow(tr);
                                            if (quarter && rowQuarter !== quarter) return;
                                            var srcTd = tds[targetColIndex];
                                            if (srcTd) setCellSelected(tr, srcTd, true);
                                        });
                                        lastClickedRowMulti = blueRow;
                                        lastClickedCellMulti = blueTd;
                                        setSelectionModeState('Grand total: sources = selected quarter rows in this column. Ctrl+click any row cell to adjust selection.');
                                        updateFormulaButtonState();
                                        return true;
                                    }
                                    if (!blueRow.classList.contains('bg-blue-100')) return false;
                                    var directMapping = getCellFormulaMapping(blueTd);
                                    var targetColIndex = getColIndex(blueTd);
                                    if (targetColIndex < 0 || targetColIndex >= fields.length) return false;
                                    var applySameRowBlueFormulaSources = function(mapping) {
                                        mapping = mapping || {};
                                        var ui = String(mapping.ui_calc_type || '').trim();
                                        var isSameRowFormulaUi = (ui === 'blue-row-formula' || ui === 'blue-row-formula-multi' || ui === 'blue-row-formula-custom');
                                        if (!isSameRowFormulaUi) return false;
                                        var keys = [];
                                        var fromSourceColumns = Array.isArray(mapping.source_columns) ? mapping.source_columns : [];
                                        var fromSourceKeys = Array.isArray(mapping.source_keys) ? mapping.source_keys : [];
                                        fromSourceColumns.forEach(function(k) { if (k && keys.indexOf(k) === -1) keys.push(k); });
                                        fromSourceKeys.forEach(function(k) { if (k && keys.indexOf(k) === -1) keys.push(k); });
                                        if (mapping.sourceA && keys.indexOf(mapping.sourceA) === -1) keys.push(mapping.sourceA);
                                        if (mapping.sourceB && keys.indexOf(mapping.sourceB) === -1) keys.push(mapping.sourceB);
                                        if (keys.length === 0) return false;
                                        var blueRowCells = window.getRowTdCells(blueRow);
                                        var picked = 0;
                                        clearSelectionMulti();
                                        setCellSelected(blueRow, blueTd, true);
                                        keys.forEach(function(k) {
                                            var idx = getFieldIndexByKeyFlexible(k);
                                            if (idx < 0 || !blueRowCells[idx]) return;
                                            setCellSelected(blueRow, blueRowCells[idx], true);
                                            if (blueRowCells[idx] !== blueTd) blueRowCells[idx].classList.add('cell-source-for-blue');
                                            picked++;
                                        });
                                        if (picked === 0) return false;
                                        lastClickedRowMulti = blueRow;
                                        lastClickedCellMulti = blueTd;
                                        setSelectionModeState('retrieved');
                                        updateFormulaButtonState();
                                        restoreCalcTypeFromMapping(mapping);
                                        return true;
                                    };
                                    if (isManualOverrideCell(blueTd)) {
                                        var hasManualSavedScope = !!(directMapping && (
                                            (Array.isArray(directMapping.row_uids) && directMapping.row_uids.length > 0) ||
                                            (Array.isArray(directMapping.row_indices) && directMapping.row_indices.length > 0)
                                        ));
                                        if (directMapping && applySameRowBlueFormulaSources(directMapping)) {
                                            return true;
                                        }
                                        if (!hasManualSavedScope) {
                                            clearSelectionMulti();
                                            setCellSelected(blueRow, blueTd, true);
                                            lastClickedRowMulti = blueRow;
                                            lastClickedCellMulti = blueTd;
                                            setSelectionModeState('Manual blue value. No saved source-cell selection to retrieve.');
                                            updateFormulaButtonState();
                                            return false;
                                        }
                                    }
                                    if (!hasMeaningfulBlueResultValue(blueTd)) {
                                        var hasScopeOnlyMapping = !!(directMapping && (
                                            (Array.isArray(directMapping.row_uids) && directMapping.row_uids.length > 0) ||
                                            (Array.isArray(directMapping.row_indices) && directMapping.row_indices.length > 0)
                                        ));
                                        if (!hasScopeOnlyMapping) {
                                            clearCellFormulaMapping(blueTd);
                                            clearSelectionMulti();
                                            setCellSelected(blueRow, blueTd, true);
                                            lastClickedRowMulti = blueRow;
                                            lastClickedCellMulti = blueTd;
                                            setSelectionModeState('Empty blue result cell. No saved formula mapping yet.');
                                            updateFormulaButtonState();
                                            return false;
                                        }
                                    }
                                    var targetKey = getFieldKey(fields[targetColIndex]);
                                    var output = directMapping;
                                    if (!output) output = findSummaryOutputByTarget(targetKey, blueRow);
                                    if (output && output.source_columns && output.source_columns.length > 0 && String(output.ui_calc_type || '').trim() !== 'blue-row-formula' && String(output.ui_calc_type || '').trim() !== 'blue-row-formula-multi' && String(output.ui_calc_type || '').trim() !== 'blue-row-formula-custom') {
                                        var hasDirect = !!directMapping;
                                        if (!hasDirect) output = null;
                                    }
                                    if (!output) {
                                        var targetVal = toNumeric(getCellRawValue(blueTd));
                                        var inferred = inferBlueRowFormulaSources(blueRow, blueTd, targetVal);
                                        if (inferred) {
                                            output = inferred.mapping;
                                            clearSelectionMulti();
                                            setCellSelected(blueRow, blueTd, true);
                                            var tds = window.getRowTdCells(blueRow);
                                            if (tds[inferred.idxA]) tds[inferred.idxA].classList.add('cell-source-for-blue');
                                            if (inferred.idxB >= 0 && tds[inferred.idxB]) tds[inferred.idxB].classList.add('cell-source-for-blue');
                                            setCellFormulaMapping(blueTd, output);
                                            setSelectionModeState('retrieved');
                                            updateFormulaButtonState();
                                            restoreCalcTypeFromMapping(output);
                                            if (typeof scheduleAutoSave === 'function') scheduleAutoSave();
                                            return true;
                                        }
                                        clearSelectionMulti();
                                        setCellSelected(blueRow, blueTd, true);
                                        lastClickedRowMulti = blueRow;
                                        lastClickedCellMulti = blueTd;
                                        setSelectionModeState('manual');
                                        updateFormulaButtonState();
                                        if (!options.silent && typeof window.showToast === 'function') window.showToast('notice', 'No saved formula mapping. Choose Formula (A & B) and Apply to set up.');
                                        return false;
                                    }
                                    output = enrichMappingWithSavedMeta(output, targetKey, blueRow);
                                    var isBlueRowFormula = String(output.ui_calc_type || '').trim() === 'blue-row-formula';
                                    var isBlueRowFormulaMulti = String(output.ui_calc_type || '').trim() === 'blue-row-formula-multi';
                                    if (isBlueRowFormula) {
                                        var sourceA = String(output.sourceA || '').trim();
                                        var sourceB = String(output.sourceB || '').trim();
                                        if (!sourceA) return false;
                                        var idxA = getFieldIndexByKeyFlexible(sourceA);
                                        var idxB = sourceB ? getFieldIndexByKeyFlexible(sourceB) : -1;
                                        if (idxA < 0) return false;
                                        clearSelectionMulti();
                                        setCellSelected(blueRow, blueTd, true);
                                        var tds = window.getRowTdCells(blueRow);
                                        if (tds[idxA]) {
                                            setCellSelected(blueRow, tds[idxA], true);
                                            if (tds[idxA] !== blueTd) tds[idxA].classList.add('cell-source-for-blue');
                                        }
                                        if (idxB >= 0 && tds[idxB]) {
                                            setCellSelected(blueRow, tds[idxB], true);
                                            if (tds[idxB] !== blueTd) tds[idxB].classList.add('cell-source-for-blue');
                                        }
                                        setSelectionModeState('retrieved');
                                        updateFormulaButtonState();
                                        restoreCalcTypeFromMapping(output);
                                        return true;
                                    }
                                    if (isBlueRowFormulaMulti) {
                                        var keys = Array.isArray(output.source_keys) ? output.source_keys : [];
                                        if (output.sourceA && keys.indexOf(output.sourceA) === -1) keys.unshift(output.sourceA);
                                        if (output.sourceB && keys.indexOf(output.sourceB) === -1) keys.push(output.sourceB);
                                        if (keys.length === 0) return false;
                                        clearSelectionMulti();
                                        setCellSelected(blueRow, blueTd, true);
                                        var tdsMulti = window.getRowTdCells(blueRow);
                                        keys.forEach(function(k) {
                                            var idx = getFieldIndexByKeyFlexible(k);
                                            if (idx >= 0 && tdsMulti[idx]) {
                                                setCellSelected(blueRow, tdsMulti[idx], true);
                                                if (tdsMulti[idx] !== blueTd) tdsMulti[idx].classList.add('cell-source-for-blue');
                                            }
                                        });
                                        setSelectionModeState('retrieved');
                                        updateFormulaButtonState();
                                        restoreCalcTypeFromMapping(output);
                                        return true;
                                    }
                                    var isBlueRowFormulaCustom = String(output.ui_calc_type || '').trim() === 'blue-row-formula-custom';
                                    if (isBlueRowFormulaCustom) {
                                        var sourceACustom = String(output.sourceA || '').trim();
                                        if (!sourceACustom) return false;
                                        var idxACustom = getFieldIndexByKeyFlexible(sourceACustom);
                                        var sourceBCustom = String(output.sourceB || '').trim();
                                        var idxBCustom = sourceBCustom ? getFieldIndexByKeyFlexible(sourceBCustom) : -1;
                                        if (idxACustom < 0) return false;
                                        clearSelectionMulti();
                                        setCellSelected(blueRow, blueTd, true);
                                        var tdsCustom = window.getRowTdCells(blueRow);
                                        if (tdsCustom[idxACustom]) {
                                            setCellSelected(blueRow, tdsCustom[idxACustom], true);
                                            if (tdsCustom[idxACustom] !== blueTd) tdsCustom[idxACustom].classList.add('cell-source-for-blue');
                                        }
                                        if (idxBCustom >= 0 && tdsCustom[idxBCustom]) {
                                            setCellSelected(blueRow, tdsCustom[idxBCustom], true);
                                            if (tdsCustom[idxBCustom] !== blueTd) tdsCustom[idxBCustom].classList.add('cell-source-for-blue');
                                        }
                                        setSelectionModeState('retrieved');
                                        updateFormulaButtonState();
                                        restoreCalcTypeFromMapping(output);
                                        return true;
                                    }
                                    output = enrichMappingWithTemplateRowScope(blueTd, blueRow, output) || output;
                                    var sectionCtx = getSectionRowsContextFromRow(blueRow);
                                    if (!sectionCtx) return false;
                                    var sourceRows = sectionCtx.dataRows.slice();
                                    var hasRowUidsScope = Array.isArray(output.row_uids) && output.row_uids.length > 0;
                                    var hasRowIndicesScope = Array.isArray(output.row_indices) && output.row_indices.length > 0;
                                    if (hasRowUidsScope) {
                                        var uidSet = {};
                                        output.row_uids.forEach(function(uid) { uidSet[String(uid)] = true; });
                                        sourceRows = sourceRows.filter(function(tr) {
                                            var uid = String(tr.getAttribute('data-row-uid') || '');
                                            return !!uidSet[uid];
                                        });
                                        // Keep blue-source persistence resilient while switching blue cells:
                                        // if UID matching fails in the live DOM, fall back to saved row indices.
                                        if (sourceRows.length === 0 && hasRowIndicesScope) {
                                            var rowsByIndexFromUidFallback = [];
                                            output.row_indices.forEach(function(idx) {
                                                var n = parseInt(idx, 10);
                                                if (!isNaN(n) && n >= 0 && n < sectionCtx.dataRows.length) {
                                                    rowsByIndexFromUidFallback.push(sectionCtx.dataRows[n]);
                                                }
                                            });
                                            if (rowsByIndexFromUidFallback.length > 0) sourceRows = rowsByIndexFromUidFallback;
                                        }
                                    } else if (hasRowIndicesScope) {
                                        var rowsByIndex = [];
                                        output.row_indices.forEach(function(idx) {
                                            var n = parseInt(idx, 10);
                                            if (!isNaN(n) && n >= 0 && n < sectionCtx.dataRows.length) {
                                                rowsByIndex.push(sectionCtx.dataRows[n]);
                                            }
                                        });
                                        sourceRows = rowsByIndex;
                                    }
                                    var hasSavedRowScope = hasRowUidsScope || hasRowIndicesScope;
                                    var sourceKeys = Array.isArray(output.source_columns) ? output.source_columns.slice() : [];
                                    var sourceA = String(output.sourceA || '');
                                    var sourceB = String(output.sourceB || '');
                                    if (sourceA && sourceKeys.indexOf(sourceA) === -1) sourceKeys.push(sourceA);
                                    if (sourceB && sourceKeys.indexOf(sourceB) === -1) sourceKeys.push(sourceB);
                                    if (sourceKeys.length === 0) {
                                        if (!options.silent && typeof window.showToast === 'function') window.showToast('notice', 'This blue result has no saved source columns.');
                                        return false;
                                    }
                                    // Fallback for legacy/incomplete blue-row mappings:
                                    // if we have source columns but no row scope, re-highlight sources from this same blue row
                                    // instead of expanding to whole section or showing nothing.
                                    if (!hasSavedRowScope) {
                                        var sameRowSourceApplied = false;
                                        var blueRowCells = window.getRowTdCells(blueRow);
                                        clearSelectionMulti();
                                        setCellSelected(blueRow, blueTd, true);
                                        sourceKeys.forEach(function(key) {
                                            var idx = getFieldIndexByKeyFlexible(key);
                                            if (idx < 0 || !blueRowCells[idx]) return;
                                            setCellSelected(blueRow, blueRowCells[idx], true);
                                            if (blueRowCells[idx] !== blueTd) blueRowCells[idx].classList.add('cell-source-for-blue');
                                            sameRowSourceApplied = true;
                                        });
                                        if (sameRowSourceApplied) {
                                            lastClickedRowMulti = blueRow;
                                            lastClickedCellMulti = blueTd;
                                            setSelectionModeState('retrieved');
                                            updateFormulaButtonState();
                                            restoreCalcTypeFromMapping(output);
                                            return true;
                                        }
                                    }
                                    // Guardrail: selection-based blue calculations must be row-scoped.
                                    // Old mappings without row_uids/row_indices used to expand to full section unexpectedly.
                                    if (!hasSavedRowScope) {
                                        clearSelectionMulti();
                                        setCellSelected(blueRow, blueTd, true);
                                        lastClickedRowMulti = blueRow;
                                        lastClickedCellMulti = blueTd;
                                        setSelectionModeState('Saved mapping has no row scope. Re-select source rows, then click Apply once.');
                                        updateFormulaButtonState();
                                        if (!options.silent && typeof window.showToast === 'function') {
                                            window.showToast('notice', 'Please re-select source rows for this blue result and click Apply once.');
                                        }
                                        return true;
                                    }
                                    // Never substitute the whole section when row_uids/row_indices were saved but none match (stale IDs, DOM order, etc.).
                                    if (!Array.isArray(sourceRows) || sourceRows.length === 0) {
                                        if (applySameRowBlueFormulaSources(output)) {
                                            return true;
                                        }
                                        clearSelectionMulti();
                                        setCellSelected(blueRow, blueTd, true);
                                        lastClickedRowMulti = blueRow;
                                        lastClickedCellMulti = blueTd;
                                        setSelectionModeState('Saved source rows not found in this section. Re-select cells and click Apply.');
                                        updateFormulaButtonState();
                                        if (!options.silent && typeof window.showToast === 'function') {
                                            window.showToast('notice', 'Could not match saved rows to the table. Re-select the source cells for this total and click Apply.');
                                        }
                                        return true;
                                    }
                                    // Preserve exact saved row scope even when some selected source cells are empty.
                                    // Reselect Cells should mirror what was originally selected, not only non-empty values.
                                    var sourceKeyIndices = sourceKeys.map(function(k) { return getFieldIndexByKeyFlexible(k); }).filter(function(i) { return i >= 0; });
                                    if (sourceKeyIndices.length === 0) {
                                        if (!options.silent && typeof window.showToast === 'function') window.showToast('notice', 'Saved source columns no longer match this template.');
                                        return false;
                                    }
                                    clearSelectionMulti();
                                    setCellSelected(blueRow, blueTd, true);
                                    var selectedSourceCount = 0;
                                    sourceRows.forEach(function(tr) {
                                        sourceKeys.forEach(function(key) {
                                            var idx = getFieldIndexByKeyFlexible(key);
                                            if (idx < 0) return;
                                            var tds = window.getRowTdCells(tr);
                                            var sourceTd = tds[idx];
                                            if (!sourceTd) return;
                                            setCellSelected(tr, sourceTd, true);
                                            selectedSourceCount++;
                                        });
                                    });
                                    lastClickedRowMulti = blueRow;
                                    lastClickedCellMulti = blueTd;
                                    setSelectionModeState('retrieved');
                                    updateFormulaButtonState();
                                    restoreCalcTypeFromMapping(output);
                                    if (!options.silent && selectedSourceCount > 0 && typeof window.showToast === 'function') {
                                        window.showToast('notice', 'Retrieved source cells for selected blue result.');
                                    }
                                    return selectedSourceCount > 0;
                                }
                                function selectCellRange(startTr, startTd, endTr, endTd) {
                                    if (!tableBody) return;
                                    var rows = tableBody.querySelectorAll('tr.data-row');
                                    var r0 = getRowIndex(startTr);
                                    var c0 = getColIndex(startTd);
                                    var r1 = getRowIndex(endTr);
                                    var c1 = getColIndex(endTd);
                                    if (r0 === -1 || r1 === -1 || c0 === -1 || c1 === -1) return;
                                    if (c0 >= fields.length || c1 >= fields.length) return;
                                    var rMin = Math.min(r0, r1);
                                    var rMax = Math.max(r0, r1);
                                    var cMin = Math.min(c0, c1);
                                    var cMax = Math.max(c0, c1);
                                    for (var r = rMin; r <= rMax; r++) {
                                        var tr = rows[r];
                                        if (tr.classList.contains('bg-blue-100')) continue;
                                        var tds = window.getRowTdCells(tr);
                                        for (var c = cMin; c <= cMax && c < tds.length; c++) {
                                            setCellSelected(tr, tds[c], true);
                                        }
                                    }
                                }

                                // Click/drag selection behavior:
                                // - plain click: select single cell only (no drag-to-range)
                                // - Shift+click: range from last clicked cell
                                // - Ctrl/Cmd+click: additive toggle; Ctrl/Cmd+drag: range selection
                                // - clicking inside input/select/textarea without modifiers: edit normally
                                if (tableBody) {
                                    tableBody.addEventListener('mousedown', function(e) {
                                        // Clicks intended for the floating "Choose calculation" panel can hit the table underneath
                                        // (e.g. flex gaps with pointer-events). Do not clear multi-selection or hide the popover.
                                        if (selectionPopover && !selectionPopover.classList.contains('hidden')) {
                                            var pr = selectionPopover.getBoundingClientRect();
                                            if (e.clientX >= pr.left && e.clientX <= pr.right && e.clientY >= pr.top && e.clientY <= pr.bottom) {
                                                return;
                                            }
                                        }
                                        var tr = e.target.closest('tr.data-row');
                                        var btn = e.target.closest('.delete-row-btn');
                                        if (btn) return;
                                        if (!tr) return;
                                        // Reselect Cells: document handler copies mapping from the clicked blue total.
                                        // Do not select that cell here (bubble order runs this before document), or row-actions
                                        // and lastClicked* briefly anchor to the source total instead of the empty target.
                                        if (pendingReselectCellsPick && reselectCellsTargetTd) {
                                            return;
                                        }

                                        var isMulti = e.ctrlKey || e.metaKey;
                                        var td = e.target.closest('td');
                                        if (!td) return;

                                        if (e.target.closest('a[href]') && !isMulti) return;

                                        var insideInput = e.target.closest('input, select, textarea');
                                        if (insideInput && !isMulti && !e.shiftKey) {
                                            // Blue / grand-total clicks: the click handler runs autoSelectSourcesForBlueCell
                                            // to restore contributing (white) cells. Clearing here first would wipe those
                                            // highlights; if auto-select then returns false, only the blue cell stays selected.
                                            if (tr.classList.contains('bg-blue-100') || tr.classList.contains('grand-total-row')) {
                                                return;
                                            }
                                            // Treat as a normal single-cell selection (so dots can show).
                                            // Do NOT open row-actions popover here.
                                            clearSelectionMulti();
                                            setCellSelected(tr, td, true);
                                            lastClickedRowMulti = tr;
                                            lastClickedCellMulti = td;
                                            setSelectionModeState('manual');
                                            updateFormulaButtonState();
                                            return;
                                        }
                                        if (insideInput && isMulti) {
                                            e.preventDefault();
                                            e.stopPropagation();
                                        }

                                        var cells = window.getRowTdCells(tr);
                                        var colIndex = cells.indexOf(td);
                                        if (colIndex === -1 || colIndex >= fields.length) return;

                                        // Shift+click: select range from last clicked cell to this cell (Excel-like)
                                        if (e.shiftKey && lastClickedRowMulti && lastClickedCellMulti) {
                                            clearSelectionMulti();
                                            selectCellRange(lastClickedRowMulti, lastClickedCellMulti, tr, td);
                                            lastClickedRowMulti = tr;
                                            lastClickedCellMulti = td;
                                            updateFormulaButtonState();
                                            e.preventDefault();
                                            return;
                                        }

                                        if (isMulti) {
                                            dragStartCell = { tr: tr, td: td };
                                            isDragSelecting = true;
                                            isDragAdditive = true;
                                            var already = td.classList.contains('cell-selected');
                                            setCellSelected(tr, td, !already);
                                            lastClickedRowMulti = tr;
                                            lastClickedCellMulti = td;
                                            setSelectionModeState('manual');
                                            updateFormulaButtonState();
                                            var selectedGT = tableBody ? Array.prototype.slice.call(tableBody.querySelectorAll('td.cell-selected')).find(function(t) {
                                                var r = t.closest('tr.data-row');
                                                return r && r.classList.contains('grand-total-row');
                                            }) : null;
                                            if (selectedGT) setGrandTotalManualOverride(selectedGT, true);
                                            e.preventDefault();
                                            if (document.body) document.body.style.userSelect = 'none';
                                            return;
                                        }

                                        // Plain click (no Ctrl): select single cell only, no drag-to-range
                                        // Defer blue / grand-total result cells to the click handler so
                                        // autoSelectSourcesForBlueCell can rebuild source highlights without an
                                        // intermediate state where only the blue cell is selected.
                                        if (tr.classList.contains('bg-blue-100') || tr.classList.contains('grand-total-row')) {
                                            e.preventDefault();
                                            return;
                                        }
                                        clearSelectionMulti();
                                        setCellSelected(tr, td, true);
                                        lastClickedRowMulti = tr;
                                        lastClickedCellMulti = td;
                                        if (!tr.classList.contains('bg-blue-100')) setSelectionModeState('manual');
                                        updateFormulaButtonState();
                                        e.preventDefault();
                                    });

                                    tableBody.addEventListener('mousemove', function(e) {
                                        if (!isDragSelecting || !dragStartCell) return;
                                        updateSelectionAutoScroll(e.clientY);
                                        var tr = e.target.closest('tr.data-row');
                                        if (!tr || tr.classList.contains('bg-blue-100')) return;
                                        var td = e.target.closest('td');
                                        if (!td) return;
                                        var colIndex = getColIndex(td);
                                        if (colIndex < 0 || colIndex >= fields.length) return;
                                        // Only switch to range selection when user hovers over a *different* cell
                                        if (tr === dragStartCell.tr && td === dragStartCell.td) return;
                                        if (!isDragAdditive) {
                                            clearSelectionMulti();
                                        }
                                        selectCellRange(dragStartCell.tr, dragStartCell.td, tr, td);
                                    });
                                }

                                document.addEventListener('mousemove', function(e) {
                                    if (isDragSelecting) updateSelectionAutoScroll(e.clientY);
                                });

                                document.addEventListener('mouseup', function() {
                                    if (isDragSelecting && tableBody) {
                                        updateFormulaButtonState();
                                    }
                                    isDragSelecting = false;
                                    isDragAdditive = false;
                                    dragStartCell = null;
                                    stopSelectionAutoScroll();
                                    if (document.body) document.body.style.userSelect = '';
                                });

                                // Keyboard: Escape = clear selection; Tab = next cell; Enter = same column next row (Excel-like)
                                if (tableContainerMulti) {
                                    function isElInsideFloatingTableChrome(el) {
                                        if (!el || !el.nodeType) return false;
                                        try {
                                            if (selectionPopover && !selectionPopover.classList.contains('hidden') && selectionPopover.contains(el)) return true;
                                            if (formulaModal && !formulaModal.classList.contains('hidden') && formulaModal.contains(el)) return true;
                                            if (autocalcModal && !autocalcModal.classList.contains('hidden') && autocalcModal.contains(el)) return true;
                                            if (aggregateChainModal && !aggregateChainModal.classList.contains('hidden') && aggregateChainModal.contains(el)) return true;
                                            if (campusTargetComparePanel && !campusTargetComparePanel.classList.contains('hidden') && campusTargetComparePanel.contains(el)) return true;
                                            if (rowActionsPopover && !rowActionsPopover.classList.contains('hidden') && rowActionsPopover.contains(el)) return true;
                                            if (rowActionsPopoverBlue && !rowActionsPopoverBlue.classList.contains('hidden') && rowActionsPopoverBlue.contains(el)) return true;
                                        } catch (err) {}
                                        return false;
                                    }
                                    // Choose calculation lives inside #table-container-multi, so keydown bubbles to the grid shortcut handler.
                                    // Stop Backspace/Delete at the popover shell so they only affect the focused input/select, not multi-selected cells.
                                    var selectionPopoverInnerEl = document.getElementById('selection-popover-inner');
                                    if (selectionPopoverInnerEl) {
                                        selectionPopoverInnerEl.addEventListener('keydown', function(e) {
                                            var t = e.target;
                                            if (!t || t === selectionPopoverInnerEl) return;
                                            var tag = t.tagName ? String(t.tagName).toUpperCase() : '';
                                            if (tag !== 'INPUT' && tag !== 'TEXTAREA' && tag !== 'SELECT') return;
                                            var k = String(e.key || '');
                                            if (k !== 'Backspace' && k !== 'Delete') return;
                                            e.stopPropagation();
                                        }, false);
                                    }
                                    tableContainerMulti.addEventListener('keydown', function(e) {
                                        // Ctrl+C / Ctrl+V cell copy/paste (paste into selected empty cells in the same column)
                                        var ctrl = !!(e.ctrlKey || e.metaKey);

                                        // Backspace/Delete: clear selected cells (data, grand total, KPI finalize) with Ctrl+Z undo. Blue summary rows skipped (no stable row uid).
                                        if (!ctrl && !e.shiftKey && (String(e.key) === 'Backspace' || String(e.key) === 'Delete') && !e.altKey) {
                                            var selectedTdsForDelete = tableBody ? tableBody.querySelectorAll('td.cell-selected') : [];
                                            if (selectedTdsForDelete && selectedTdsForDelete.length > 0) {
                                                // Key events from inputs in "Choose calculation" / modals bubble up because those nodes live inside #table-container-multi.
                                                // Never wipe multi-selected table cells when editing e.g. Count unique (A+/- adjust) amount.
                                                if (isElInsideFloatingTableChrome(e.target) || isElInsideFloatingTableChrome(document.activeElement)) {
                                                    return;
                                                }
                                                // If user is editing a single cell, let Backspace behave normally (delete one character),
                                                // unless it's a grand-total cell with the entire input selected (Ctrl+A then Delete) - then use batch clear + undo.
                                                var activeEl = document.activeElement;
                                                if (selectedTdsForDelete.length === 1 && activeEl && (activeEl.tagName === 'INPUT' || activeEl.tagName === 'TEXTAREA')) {
                                                    var activeTd = activeEl.closest && activeEl.closest('td');
                                                    if (activeTd && selectedTdsForDelete[0] === activeTd) {
                                                        var trOne = activeTd.closest('tr.data-row');
                                                        var skipNativeTyping = false;
                                                        if (trOne && trOne.classList.contains('grand-total-row') && !activeEl.readOnly && !activeEl.disabled) {
                                                            try {
                                                                var st = activeEl.selectionStart;
                                                                var en = activeEl.selectionEnd;
                                                                var vlen = String(activeEl.value || '').length;
                                                                if (st != null && en != null && st !== en && vlen > 0 &&
                                                                    Math.min(st, en) === 0 && Math.max(st, en) >= vlen) {
                                                                    skipNativeTyping = true;
                                                                }
                                                            } catch (se) {}
                                                        }
                                                        if (!skipNativeTyping) {
                                                            return; // allow normal backspace/delete inside input
                                                        }
                                                    }
                                                }

                                                var undoBatchDel = [];
                                                var appliedDel = 0;
                                                selectedTdsForDelete.forEach(function(td) {
                                                    if (!td) return;
                                                    var tr = td.closest('tr.data-row');
                                                    if (!tr) return;
                                                    if (tr.classList.contains('bg-blue-100')) return;
                                                    var inputEl = getEditableInputElFromTd(td);
                                                    if (!inputEl || inputEl.readOnly || inputEl.disabled) return;
                                                    var read = readValueFromTd(td);
                                                    if (!read) return;
                                                    if (isCellEmptyValue(read.value)) return;
                                                    var colIdx = getColIndex(td);
                                                    if (colIdx < 0) return;
                                                    var rowUid = ensureDataRowUid(tr);
                                                    if (!rowUid) return;
                                                    var ok = writeValueToTd(td, String(read.fieldKey), '');
                                                    if (ok) {
                                                        appliedDel++;
                                                        undoBatchDel.push({
                                                            rowUid: rowUid,
                                                            colIdx: colIdx,
                                                            fieldKey: read.fieldKey,
                                                            before: String(read.value),
                                                            after: ''
                                                        });
                                                    }
                                                });

                                                if (appliedDel > 0) {
                                                    pushCellEditUndoBatch(undoBatchDel);
                                                    window.tableDataDirty = true;
                                                    if (typeof scheduleAutoSave === 'function') scheduleAutoSave();
                                                    if (typeof setAutosaveStatus === 'function') setAutosaveStatus('saving');
                                                    if (typeof hideRowActionsPopover === 'function') hideRowActionsPopover();
                                                    e.preventDefault();
                                                    return;
                                                }
                                            }
                                        }

                                        if (ctrl && e.shiftKey && (String(e.key).toLowerCase() === 'c' || String(e.key).toLowerCase() === 'v')) {
                                            if (isElInsideFloatingTableChrome(e.target) || isElInsideFloatingTableChrome(document.activeElement)) return;
                                            var keyLower = String(e.key).toLowerCase();
                                            // Ctrl+Shift+C: copy one or more full rows into localStorage (cross-template)
                                            // - Multiple td.cell-selected across rows a each row captured in table order
                                            // - Otherwise a focused / last-clicked row
                                            if (keyLower === 'c') {
                                                function sortTrsDocOrder(list) {
                                                    return list.slice().sort(function(a, b) {
                                                        if (!a || !b || a === b) return 0;
                                                        var pos = a.compareDocumentPosition(b);
                                                        if (pos & Node.DOCUMENT_POSITION_FOLLOWING) return -1;
                                                        if (pos & Node.DOCUMENT_POSITION_PRECEDING) return 1;
                                                        return 0;
                                                    });
                                                }
                                                function collectOneRowDataFromTr(tr, fieldKeysToCollect) {
                                                    var rowData = {};
                                                    var tds = window.getRowTdCells(tr);
                                                    for (var i = 0; i < tds.length; i++) {
                                                        var td = tds[i];
                                                        var colIdx = getColIndex(td);
                                                        if (colIdx < 0 || colIdx >= fields.length) continue;
                                                        var fieldKey = getFieldKey(fields[colIdx]);
                                                        if (fieldKeysToCollect && !fieldKeysToCollect.has(String(fieldKey))) continue;
                                                        var inputEl = td.querySelector('input[name], select[name], textarea[name]');
                                                        if (!inputEl) continue;
                                                        var val = inputEl.tagName === 'SELECT' ? inputEl.value : (inputEl.value || '');
                                                        rowData[String(fieldKey)] = isCellEmptyValue(val) ? '' : String(val);
                                                    }
                                                    return rowData;
                                                }

                                                var sourceTrList = [];
                                                var selTdsCopy = tableBody ? tableBody.querySelectorAll('td.cell-selected') : [];
                                                var fieldKeysToCollect = null;
                                                if (selTdsCopy && selTdsCopy.length > 0) {
                                                    var keysSetSA = new Set();
                                                    selTdsCopy.forEach(function(td) {
                                                        if (!td) return;
                                                        var colIdxSA = getColIndex(td);
                                                        if (colIdxSA < 0 || colIdxSA >= fields.length) return;
                                                        var fkSA = getFieldKey(fields[colIdxSA]);
                                                        if (fkSA) keysSetSA.add(String(fkSA));
                                                    });
                                                    if (keysSetSA.size > 0) fieldKeysToCollect = keysSetSA;
                                                }
                                                if (selTdsCopy && selTdsCopy.length > 0) {
                                                    var seenCopy = new Set();
                                                    selTdsCopy.forEach(function(td) {
                                                        if (!td) return;
                                                        var tr = td.closest('tr.data-row');
                                                        if (!tr || tr.classList.contains('bg-blue-100') || tr.classList.contains('grand-total-row')) return;
                                                        if (seenCopy.has(tr)) return;
                                                        seenCopy.add(tr);
                                                        sourceTrList.push(tr);
                                                    });
                                                    sourceTrList = sortTrsDocOrder(sourceTrList);
                                                }
                                                if (sourceTrList.length === 0) {
                                                    var srcTd = lastClickedCellMulti;
                                                    if (!srcTd) {
                                                        var activeC = document.activeElement;
                                                        if (activeC && tableContainerMulti.contains(activeC)) srcTd = activeC.closest('td');
                                                    }
                                                    if (!srcTd) return;
                                                    var srcTr = srcTd.closest('tr.data-row');
                                                    if (!srcTr) return;
                                                    if (srcTr.classList.contains('bg-blue-100') || srcTr.classList.contains('grand-total-row')) return;
                                                    sourceTrList = [srcTr];
                                                }

                                                var rowsPayload = [];
                                                for (var si = 0; si < sourceTrList.length; si++) {
                                                    rowsPayload.push(collectOneRowDataFromTr(sourceTrList[si], fieldKeysToCollect));
                                                }
                                                try {
                                                    localStorage.setItem(ROW_COPY_BUFFER_KEY, JSON.stringify({ v: 2, rows: rowsPayload }));
                                                    var cmsg = rowsPayload.length === 1
                                                        ? 'Copied 1 row (cross-template).'
                                                        : ('Copied ' + rowsPayload.length + ' rows (cross-template).');
                                                    if (typeof window.showToast === 'function') window.showToast('notice', cmsg);
                                                } catch (err) {
                                                    if (typeof window.showAlert === 'function') window.showAlert({ title: 'Notice', message: 'Row copy failed (storage blocked).' });
                                                }
                                                e.preventDefault();
                                                return;
                                            }

                                            // Ctrl+Shift+V: paste stored row(s) into selected row(s), 1:1 when multiple sources
                                            if (keyLower === 'v') {
                                                var raw = null;
                                                try { raw = localStorage.getItem(ROW_COPY_BUFFER_KEY); } catch (err2) {}
                                                if (!raw) return;
                                                var parsedClip = null;
                                                try { parsedClip = JSON.parse(raw || '{}'); } catch (err3) { parsedClip = null; }
                                                if (!parsedClip || typeof parsedClip !== 'object') return;
                                                var clipboardRows = [];
                                                if (parsedClip.v === 2 && Array.isArray(parsedClip.rows)) {
                                                    clipboardRows = parsedClip.rows.filter(function(r) { return r && typeof r === 'object'; });
                                                } else {
                                                    clipboardRows = [parsedClip];
                                                }
                                                if (!clipboardRows.length) return;

                                                function sortTrsDocOrderPaste(list) {
                                                    return list.slice().sort(function(a, b) {
                                                        if (!a || !b || a === b) return 0;
                                                        var pos = a.compareDocumentPosition(b);
                                                        if (pos & Node.DOCUMENT_POSITION_FOLLOWING) return -1;
                                                        if (pos & Node.DOCUMENT_POSITION_PRECEDING) return 1;
                                                        return 0;
                                                    });
                                                }

                                                // For row-paste, derive target rows from the currently selected cells,
                                                // but only where the selected cell itself is empty. This prevents
                                                // leftover selections from pasting into unintended rows.
                                                var targetTrs = [];
                                                var selectedTdsForRow = tableBody ? tableBody.querySelectorAll('td.cell-selected') : [];
                                                if (selectedTdsForRow && selectedTdsForRow.length > 0) {
                                                    var uniqueTr = new Set();
                                                    selectedTdsForRow.forEach(function(td) {
                                                        if (!td) return;
                                                        var readCell = readValueFromTd(td);
                                                        if (!readCell) return;
                                                        if (!isCellEmptyValue(readCell.value)) return;
                                                        var tr = td.closest('tr.data-row');
                                                        if (!tr) return;
                                                        if (tr.classList.contains('bg-blue-100') || tr.classList.contains('grand-total-row')) return;
                                                        uniqueTr.add(tr);
                                                    });
                                                    targetTrs = sortTrsDocOrderPaste(Array.from(uniqueTr));
                                                }

                                                // Fallback to the selected row set if we couldn't infer from selected cells.
                                                if (!targetTrs || targetTrs.length === 0) {
                                                    if (selectedRowsMulti && selectedRowsMulti.length > 0) {
                                                        targetTrs = sortTrsDocOrderPaste(selectedRowsMulti.slice());
                                                    } else if (lastClickedRowMulti && lastClickedRowMulti.closest && lastClickedRowMulti.closest('tr.data-row')) {
                                                        targetTrs = [lastClickedRowMulti];
                                                    } else {
                                                        var active2 = document.activeElement;
                                                        if (active2) {
                                                            var tr2 = active2.closest && active2.closest('tr.data-row');
                                                            if (tr2) targetTrs = [tr2];
                                                        }
                                                    }
                                                }

                                                if (!targetTrs || targetTrs.length === 0) return;

                                                var applied2 = 0;
                                                for (var ri = 0; ri < targetTrs.length; ri++) {
                                                    var tr = targetTrs[ri];
                                                    if (!tr || tr.classList.contains('bg-blue-100') || tr.classList.contains('grand-total-row')) continue;
                                                    var rowData2 = clipboardRows.length === 1 ? clipboardRows[0] : clipboardRows[ri];
                                                    if (!rowData2) break;
                                                    var tds2 = window.getRowTdCells(tr);
                                                    for (var j = 0; j < tds2.length; j++) {
                                                        var td2 = tds2[j];
                                                        var colIdx2 = getColIndex(td2);
                                                        if (colIdx2 < 0 || colIdx2 >= fields.length) continue;
                                                        var fk2 = getFieldKey(fields[colIdx2]);
                                                        if (!Object.prototype.hasOwnProperty.call(rowData2, String(fk2))) continue;
                                                        var cellInputEl = null;
                                                        var candidates = td2.querySelectorAll('input[name], select[name], textarea[name]');
                                                        for (var k = 0; k < candidates.length; k++) {
                                                            var cand = candidates[k];
                                                            if (!cand) continue;
                                                            var candName = cand.getAttribute && cand.getAttribute('name');
                                                            if (String(candName) === String(fk2)) {
                                                                cellInputEl = cand;
                                                                break;
                                                            }
                                                        }
                                                        if (!cellInputEl) continue;
                                                        var currentVal = cellInputEl.tagName === 'SELECT' ? cellInputEl.value : (cellInputEl.value || '');
                                                        if (!isCellEmptyValue(currentVal)) continue;

                                                        var nextVal = rowData2[String(fk2)];
                                                        if (isCellEmptyValue(nextVal)) continue;
                                                        if (cellInputEl.tagName === 'SELECT') {
                                                            cellInputEl.value = nextVal || '';
                                                            cellInputEl.dispatchEvent(new Event('change', { bubbles: true }));
                                                        } else {
                                                            cellInputEl.value = nextVal || '';
                                                            cellInputEl.dispatchEvent(new Event('input', { bubbles: true }));
                                                            cellInputEl.dispatchEvent(new Event('change', { bubbles: true }));
                                                        }

                                                        var aEl = td2.querySelector('a[href]');
                                                        if (aEl && nextVal) aEl.setAttribute('href', nextVal);

                                                        applied2++;
                                                    }
                                                }

                                                if (applied2 > 0) {
                                                    window.tableDataDirty = true;
                                                    if (typeof scheduleAutoSave === 'function') scheduleAutoSave();
                                                    if (typeof setAutosaveStatus === 'function') setAutosaveStatus('saving');
                                                    if (typeof window.showToast === 'function') window.showToast('notice', 'Pasted row(s) into target template.');
                                                } else {
                                                    if (typeof window.showToast === 'function') window.showToast('notice', 'No empty cells to paste into.');
                                                }
                                                e.preventDefault();
                                                return;
                                            }
                                        }
                                        if (e.key === 'Escape') {
                                            clearSelectionMulti();
                                            if (formulaModal && !formulaModal.classList.contains('hidden')) {
                                                hideFormulaModal();
                                            }
                                            if (autocalcModal && !autocalcModal.classList.contains('hidden')) {
                                                hideAutocalcModal();
                                            }
                                            e.preventDefault();
                                            return;
                                        }
                                        var active = document.activeElement;
                                        if (!active || !tableContainerMulti.contains(active)) return;
                                        var td = active.closest('td');
                                        var tr = active.closest('tr.data-row');
                                        if (!tr || !td || tr.classList.contains('section-header-row')) return;
                                        if (e.key === 'Tab') {
                                            var cells = window.getRowTdCells(tr);
                                            var idx = cells.indexOf(td);
                                            if (idx === -1) return;
                                            if (e.shiftKey) {
                                                if (idx <= 0) return;
                                                var prev = cells[idx - 1];
                                                var nextInput = prev.querySelector('input, select, textarea');
                                                if (nextInput) {
                                                    e.preventDefault();
                                                    nextInput.focus();
                                                }
                                            } else {
                                                if (idx >= cells.length - 1) return;
                                                var next = cells[idx + 1];
                                                var nextInput = next.querySelector('input, select, textarea');
                                                if (nextInput) {
                                                    e.preventDefault();
                                                    nextInput.focus();
                                                }
                                            }
                                            return;
                                        }
                                        if (e.key === 'Enter') {
                                            var rows = Array.prototype.slice.call(tableBody.querySelectorAll('tr.data-row')).filter(function(r) { return !r.classList.contains('section-header-row'); });
                                            var rowIdx = rows.indexOf(tr);
                                            if (rowIdx === -1) return;
                                            var colIdx = getColIndex(td);
                                            if (colIdx < 0) return;
                                            var nextTr = rows[rowIdx + 1];
                                            if (!nextTr) return;
                                            var nextTds = window.getRowTdCells(nextTr);
                                            var nextTd = nextTds[colIdx];
                                            if (!nextTd) return;
                                            var nextInput = nextTd.querySelector('input, select, textarea');
                                            if (nextInput) {
                                                e.preventDefault();
                                                nextInput.focus();
                                            }
                                        }
                                    });

                                    document.addEventListener('keydown', function cellUndoRedoGlobal(e) {
                                        if (!tableBody || !tableContainerMulti) return;
                                        var ctrl = !!(e.ctrlKey || e.metaKey);
                                        if (!ctrl || e.altKey) return;
                                        var zk = String(e.key || '').toLowerCase();
                                        var isUndo = zk === 'z' && !e.shiftKey;
                                        var isRedo = zk === 'y' || (zk === 'z' && e.shiftKey);
                                        if (!isUndo && !isRedo) return;
                                        if (isElInsideFloatingTableChrome(e.target) || isElInsideFloatingTableChrome(document.activeElement)) return;
                                        var act = document.activeElement;
                                        if (act && (act.tagName === 'INPUT' || act.tagName === 'TEXTAREA') && (!tableBody.contains(act))) return;
                                        if (activeElementIsTableCellTyping()) return;
                                        if (isUndo) {
                                            if (performCellUndo()) e.preventDefault();
                                        } else if (isRedo) {
                                            if (performCellRedo()) e.preventDefault();
                                        }
                                    }, true);

                                    tableContainerMulti.addEventListener('copy', function(e) {
                                        if (isElInsideFloatingTableChrome(e.target)) return;
                                        if (!tableBody) return;
                                        var selNodes = tableBody.querySelectorAll('td.cell-selected');
                                        var sourceTds = [];
                                        if (selNodes && selNodes.length > 0) {
                                            sourceTds = Array.prototype.slice.call(selNodes);
                                        } else if (lastClickedCellMulti) {
                                            var tCopy = e.target;
                                            if (tCopy && (tCopy.tagName === 'INPUT' || tCopy.tagName === 'TEXTAREA')) {
                                                try {
                                                    if (tCopy.selectionStart != null && tCopy.selectionEnd != null && tCopy.selectionStart !== tCopy.selectionEnd) return;
                                                } catch (scErr) {}
                                            }
                                            sourceTds = [lastClickedCellMulti];
                                        } else return;

                                        var clipRows = buildClipboardRowsFromSelection(sourceTds);
                                        if (!clipRows.length) return;

                                        var hasAny = false;
                                        for (var hi = 0; hi < clipRows.length && !hasAny; hi++) {
                                            for (var hj = 0; hj < clipRows[hi].length; hj++) {
                                                if (!isCellEmptyValue(clipRows[hi][hj])) { hasAny = true; break; }
                                            }
                                        }
                                        if (!hasAny) {
                                            if (typeof window.showToast === 'function') window.showToast('notice', 'Selection is empty.');
                                            else if (typeof window.showAlert === 'function') window.showAlert({ title: 'Notice', message: 'Selection is empty.' });
                                            e.preventDefault();
                                            return;
                                        }

                                        var sortedOne = sortSelectedTdsRowMajor(sourceTds);
                                        var firstRead = sortedOne[0] ? readValueFromTd(sortedOne[0]) : null;
                                        cellPasteClipboard = {
                                            v: 3,
                                            rows: clipRows.map(function(r) { return r.map(function(v) { return { v: v }; }); }),
                                            primaryFieldKey: firstRead ? String(firstRead.fieldKey) : ''
                                        };

                                        var tsv = clipRows.map(function(row) {
                                            return row.map(function(c) { return String(c).replace(/\r|\n|\t/g, ' '); }).join('\t');
                                        }).join('\n');
                                        try {
                                            e.clipboardData.setData('text/plain', tsv);
                                        } catch (cbErr) {}
                                        e.preventDefault();

                                        var ncells = clipRows.reduce(function(a, r) { return a + r.length; }, 0);
                                        var msg = ncells === 1 ? 'Copied cell value.' : ('Copied ' + ncells + ' cells.');
                                        if (typeof window.showToast === 'function') window.showToast('notice', msg);
                                    }, true);

                                    tableContainerMulti.addEventListener('paste', function(e) {
                                        if (isElInsideFloatingTableChrome(e.target)) return;
                                        if (!tableBody || !tableContainerMulti) return;

                                        var text = '';
                                        try {
                                            text = (e.clipboardData || window.clipboardData).getData('text/plain') || '';
                                        } catch (pe) { text = ''; }

                                        var osGrid = text ? parsePlainTextToValueGrid(text) : null;
                                        var osIsGrid = osGrid && isProbablyValueGrid(osGrid);

                                        var internalGrid = null;
                                        if (cellPasteClipboard && cellPasteClipboard.v === 3 && Array.isArray(cellPasteClipboard.rows)) {
                                            internalGrid = cellPasteClipboard.rows.map(function(r) {
                                                return r.map(function(c) { return String((c && c.v !== undefined) ? c.v : ''); });
                                            });
                                        }
                                        var internalIsGrid = internalGrid && isProbablyValueGrid(internalGrid);

                                        function pasteDone(applied, emptyMsg) {
                                            if (applied > 0) {
                                                window.tableDataDirty = true;
                                                if (typeof scheduleAutoSave === 'function') scheduleAutoSave();
                                                if (typeof setAutosaveStatus === 'function') setAutosaveStatus('saving');
                                                if (typeof window.showToast === 'function') {
                                                    window.showToast('notice', applied === 1 ? 'Pasted into 1 cell.' : ('Pasted into ' + applied + ' cells.'));
                                                }
                                            } else if (emptyMsg && typeof window.showToast === 'function') {
                                                window.showToast('notice', emptyMsg);
                                            }
                                        }

                                        var tagPaste = e.target && e.target.tagName ? String(e.target.tagName).toUpperCase() : '';
                                        if (String(text).replace(/^\s+|\s+$/g, '') !== '' && !osIsGrid && (tagPaste === 'INPUT' || tagPaste === 'TEXTAREA' || tagPaste === 'SELECT')) {
                                            return;
                                        }

                                        var pasteableMultiSel = getPasteableSelectedTdsSorted();
                                        var multiHint = 'Could not paste into selection (editable data cells only).';
                                        if (pasteableMultiSel.length >= 2) {
                                            if (osIsGrid) {
                                                e.preventDefault();
                                                e.stopPropagation();
                                                pasteDone(pasteValueGridOntoMultiSelection(osGrid), multiHint);
                                                return;
                                            }
                                            if (internalIsGrid) {
                                                e.preventDefault();
                                                e.stopPropagation();
                                                pasteDone(pasteValueGridOntoMultiSelection(internalGrid), multiHint);
                                                return;
                                            }
                                            if (String(text).replace(/^\s+|\s+$/g, '') !== '') {
                                                e.preventDefault();
                                                e.stopPropagation();
                                                pasteDone(pastePlainTextToMultiSelection(text), multiHint);
                                                return;
                                            }
                                        }

                                        if (osIsGrid) {
                                            e.preventDefault();
                                            e.stopPropagation();
                                            var anchorOs = getPasteAnchorTd();
                                            if (!anchorOs) return;
                                            pasteDone(pasteValueGridFromAnchor(anchorOs, osGrid), 'No cells updated from paste.');
                                            return;
                                        }
                                        if (internalIsGrid) {
                                            e.preventDefault();
                                            e.stopPropagation();
                                            var anchorIn = getPasteAnchorTd();
                                            if (!anchorIn) return;
                                            pasteDone(pasteValueGridFromAnchor(anchorIn, internalGrid), 'No cells updated from paste.');
                                            return;
                                        }
                                        if (internalGrid && internalGrid.length === 1 && internalGrid[0].length === 1 && cellPasteClipboard.primaryFieldKey) {
                                            var vOne = internalGrid[0][0];
                                            if (!isCellEmptyValue(vOne)) {
                                                e.preventDefault();
                                                e.stopPropagation();
                                                pasteDone(pasteSingleValueToMatchingColumn(vOne, cellPasteClipboard.primaryFieldKey), 'No matching cells to paste into.');
                                            }
                                            return;
                                        }
                                        if (cellPasteClipboard && !cellPasteClipboard.v && cellPasteClipboard.fieldKey !== undefined && cellPasteClipboard.value !== undefined) {
                                            if (!isCellEmptyValue(cellPasteClipboard.value)) {
                                                e.preventDefault();
                                                e.stopPropagation();
                                                pasteDone(pasteSingleValueToMatchingColumn(cellPasteClipboard.value, cellPasteClipboard.fieldKey), 'No matching cells to paste into.');
                                            }
                                        }
                                    }, true);
                                }

                                function normalizeBlueRowDashes() {
                                    if (!tableBody) return;
                                    var blueRows = tableBody.querySelectorAll('tr.data-row.bg-blue-100');
                                    blueRows.forEach(function(tr) {
                                        var tds = window.getRowTdCells(tr);
                                        tds.forEach(function(td) {
                                            var input = td.querySelector('input, textarea, select');
                                            if (input) {
                                                var v = (input.value || '').trim();
                                                if (v === '') {
                                                    input.value = '';
                                                }
                                            } else {
                                                var text = (td.textContent || '').trim();
                                                if (text === '') {
                                                    td.textContent = '';
                                                }
                                            }
                                        });
                                    });
                                }
                                function dedupeAdjacentBlueSummaryRowsOnLoad() {
                                    if (!tableBody) return 0;
                                    var rows = Array.prototype.slice.call(tableBody.querySelectorAll('tr.data-row'));
                                    var removed = 0;
                                    function isBlueSummaryRow(tr) {
                                        return !!(tr
                                            && tr.classList
                                            && tr.classList.contains('bg-blue-100')
                                            && String(tr.getAttribute('data-manual-total-row') || '') !== '1'
                                            && !tr.classList.contains('grand-total-row')
                                            && !tr.classList.contains('kpi-finalize-total-row'));
                                    }
                                    for (var i = 1; i < rows.length; i++) {
                                        var prev = rows[i - 1];
                                        var cur = rows[i];
                                        if (!isBlueSummaryRow(prev) || !isBlueSummaryRow(cur)) continue;
                                        var prevSub = String(prev.getAttribute('data-submission-id') || '');
                                        var curSub = String(cur.getAttribute('data-submission-id') || '');
                                        var prevUser = String(prev.getAttribute('data-user-id') || '');
                                        var curUser = String(cur.getAttribute('data-user-id') || '');
                                        if (prevSub !== curSub || prevUser !== curUser) continue;
                                        // If two blue summary rows are consecutive in the same block, keep only one.
                                        // Keep the last row (cur) so latest user edits survive.
                                        prev.remove();
                                        removed++;
                                    }
                                    return removed;
                                }

                                if (tableBody) {
                                    var addGrandTotalBtn = document.getElementById('add-grand-total-btn');
                                    var finalizeKpiBtn = document.getElementById('finalize-kpi-btn');
                                    var addGrandTotalRowEl = document.getElementById('add-grand-total-row');
                                    var grandTotalTemplate = document.getElementById('grand-total-row-template');
                                    var kpiFinalizeTotalTemplate = document.getElementById('kpi-finalize-total-row-template');
                                    var manualTotalEmptyRowTemplate = document.getElementById('manual-total-empty-row-template');
                                    function pickLastSectionKeysForManualTotalRow() {
                                        var sub = '';
                                        var user = '';
                                        if (tableBody && tableBody.dataset && tableBody.dataset.lastSubmissionId) {
                                            sub = String(tableBody.dataset.lastSubmissionId || '');
                                        }
                                        var walk = addGrandTotalRowEl ? addGrandTotalRowEl.previousElementSibling : null;
                                        while (walk) {
                                            if (walk.id === 'manual-total-empty-row-template') {
                                                walk = walk.previousElementSibling;
                                                continue;
                                            }
                                            if (String(walk.getAttribute('data-manual-total-row') || '') === '1') {
                                                walk = walk.previousElementSibling;
                                                continue;
                                            }
                                            var ds = walk.getAttribute ? walk.getAttribute('data-submission-id') : null;
                                            if (ds !== null && String(ds).trim() !== '') {
                                                sub = String(ds);
                                                user = String((walk.getAttribute('data-user-id') || ''));
                                                break;
                                            }
                                            walk = walk.previousElementSibling;
                                        }
                                        return { submissionId: sub, userId: user };
                                    }
                                    var grandTotalColorSchemes = [
                                        { row: 'bg-amber-100 border-t-2 border-amber-300 border-l-4 border-amber-400', cell: 'border-amber-200', text: 'text-amber-900' },
                                        { row: 'bg-emerald-100 border-t-2 border-emerald-300 border-l-4 border-emerald-400', cell: 'border-emerald-200', text: 'text-emerald-900' },
                                        { row: 'bg-teal-100 border-t-2 border-teal-300 border-l-4 border-teal-400', cell: 'border-teal-200', text: 'text-teal-900' },
                                        { row: 'bg-sky-100 border-t-2 border-sky-300 border-l-4 border-sky-400', cell: 'border-sky-200', text: 'text-sky-900' },
                                        { row: 'bg-violet-100 border-t-2 border-violet-300 border-l-4 border-violet-400', cell: 'border-violet-200', text: 'text-violet-900' },
                                        { row: 'bg-rose-100 border-t-2 border-rose-300 border-l-4 border-rose-400', cell: 'border-rose-200', text: 'text-rose-900' }
                                    ];
                                    /**
                                     * Match body/blue summary row: whole numbers omit ".00" when not a percentage column.
                                     * Blue-row percentage cells use formatBlueSummaryPercentWhole (integers); finalized KPI row may still use decimals where noted.
                                     */
                                    function formatFinalizeOverallDisplayValue(n, asPercentage) {
                                        var x = Number(n);
                                        if (!isFinite(x)) return String(n);
                                        var r2 = Math.round(x * 100) / 100;
                                        if (asPercentage) return r2.toFixed(2);
                                        if (Math.abs(r2 - Math.round(r2)) < 1e-9) return String(Math.round(r2));
                                        var s = r2.toFixed(2);
                                        if (s.indexOf('.') !== -1) s = s.replace(/0+$/, '').replace(/\.$/, '');
                                        return s;
                                    }
                                    /** Detect if the finalized value column holds % / rate metrics (field def or grand-total cell shows %). */
                                    function finalizeColumnIsPercentage(colIdx) {
                                        if (colIdx < 0 || colIdx >= fields.length) return false;
                                        var f = fields[colIdx];
                                        var norm = normalizeMetricText(getFieldKey(f) + '_' + (f.label || ''));
                                        if (typeof isFieldPercentage === 'function' && isFieldPercentage(f, norm)) return true;
                                        if (!tableBody) return false;
                                        var gtRows = tableBody.querySelectorAll('tr.grand-total-row:not(#grand-total-row-template)');
                                        for (var ri = 0; ri < gtRows.length; ri++) {
                                            var tds = window.getRowTdCells(gtRows[ri]);
                                            if (colIdx >= tds.length) continue;
                                            var raw = String(getCellRawValue(tds[colIdx]) || '').trim();
                                            if (raw.indexOf('%') !== -1) return true;
                                        }
                                        return false;
                                    }
                                    function applyGrandTotalColor(tr, scheme) {
                                        var s = scheme || grandTotalColorSchemes[0];
                                        tr.className = 'grand-total-row data-row group ' + s.row;
                                        window.getRowTdCells(tr).forEach(function(td) {
                                            td.className = td.className.replace(/\bborder-r\s+border-\w+-\d+\b/, 'border-r ' + s.cell).replace(/\btext-\w+-\d+\b/g, s.text);
                                        });
                                        tr.querySelectorAll('span, input').forEach(function(el) {
                                            el.className = (el.className || '').replace(/\btext-\w+-\d+\b/g, s.text);
                                        });
                                    }
                                    function detectQuarterFromGrandTotalRow(tr) {
                                        if (!tr) return null;
                                        var tds = window.getRowTdCells(tr);
                                        if (!tds.length) return null;
                                        var labelTxt = String(getCellRawValue(tds[0]) || '').trim();
                                        var m = labelTxt.match(/\(Q\s*([1-4])\s*\)/i);
                                        if (m) return parseInt(m[1], 10);
                                        return detectQuarterFromRow(tr);
                                    }
                                    function looksLikeQuarterPickerCell(raw) {
                                        return /^\s*\d{1,2}(st|nd|rd|th)\s+Q\s*$/i.test(String(raw || '').trim());
                                    }
                                    function fieldIndexLooksLikeQuarterField(idx) {
                                        if (idx < 0 || idx >= fields.length) return false;
                                        var f = fields[idx] || {};
                                        var n = normalizeMetricText(getFieldKey(f) + '_' + (f.label || ''));
                                        return /(^|_)quarter($|_)|(^|_)qtr($|_)/.test(n) || (/(^|_)q($|_)/.test(n) && !/accomp|target|rate|variance|rating/.test(n));
                                    }
                                    function fieldIndexLooksLikeTargetOnly(idx) {
                                        if (idx < 0 || idx >= fields.length) return false;
                                        var f = fields[idx] || {};
                                        var n = normalizeMetricText(getFieldKey(f) + '_' + (f.label || ''));
                                        return /target/.test(n) && !/accomp|accomplishment|actual/.test(n);
                                    }
                                    function fieldIndexLooksLikeAccompQuarter(idx) {
                                        if (idx < 0 || idx >= fields.length) return false;
                                        var f = fields[idx] || {};
                                        var n = normalizeMetricText(getFieldKey(f) + '_' + (f.label || ''));
                                        var q = detectQuarterFromMetric(n);
                                        return !!(q && /accomp|accomplishment|actual/.test(n));
                                    }
                                    /** Per Q1aQ4 grand total row: use label (Qn) for quarter, then rightmost numeric cell (the real total), not "most hits" column (which ties on wrong early columns). */
                                    function getFinalizeQuarterRowValueCol(tr) {
                                        var tds = window.getRowTdCells(tr);
                                        var qFieldIdx = getQuarterColumnIndex();
                                        for (var c = tds.length - 1; c >= 1; c--) {
                                            if (qFieldIdx >= 0 && c === qFieldIdx) continue;
                                            if (fieldIndexLooksLikeQuarterField(c)) continue;
                                            var raw = String(getCellRawValue(tds[c]) || '').trim();
                                            if (!raw || raw === '-') continue;
                                            if (looksLikeQuarterPickerCell(raw)) continue;
                                            var n = toNumeric(raw);
                                            if (isNaN(n)) continue;
                                            return c;
                                        }
                                        return -1;
                                    }
                                    /** Read Q1aQ4 figures from the grand total row inputs only. Do not pull from campus data rows here - the first row is often 0 and would overwrite 51/7/5/6 and zero the blue row + overall total. */
                                    function getFinalizeQuarterTotals() {
                                        var rows = Array.prototype.slice.call(tableBody.querySelectorAll('tr.grand-total-row')).filter(function(tr) {
                                            return tr && tr.id !== 'grand-total-row-template';
                                        });
                                        var out = { 1: 0, 2: 0, 3: 0, 4: 0 };
                                        if (rows.length === 0) return { values: out, sourceCol: -1, mode: 'quarter', contributingQuarters: [] };
                                        // Single grand total row (Average mode): use that row's computed value as Q1.
                                        if (rows.length === 1) {
                                            var onlyTr = rows[0];
                                            var onlyTds = window.getRowTdCells(onlyTr);
                                            var bestCol = -1;
                                            var bestVal = NaN;
                                            for (var oc = 0; oc < onlyTds.length; oc++) {
                                                var tdOnly = onlyTds[oc];
                                                if (!tdOnly) continue;
                                                var mapOnly = getCellFormulaMapping(tdOnly) || {};
                                                var opOnly = String(mapOnly.ui_formula_operation || '').trim();
                                                var isAvgLike = (opOnly === 'avg' || opOnly === 'avg_number' || opOnly === 'avg_percentage' || String(mapOnly.grand_total_ctc_aggregate || '') === '1');
                                                if (!isAvgLike) continue;
                                                var nOnly = toNumeric(getCellRawValue(tdOnly));
                                                if (isNaN(nOnly)) continue;
                                                bestCol = oc;
                                                bestVal = nOnly;
                                                break;
                                            }
                                            if (bestCol < 0) {
                                                for (var oc2 = 0; oc2 < onlyTds.length; oc2++) {
                                                    var rawOnly = String(getCellRawValue(onlyTds[oc2]) || '').trim();
                                                    if (!rawOnly || rawOnly === '-' || looksLikeQuarterPickerCell(rawOnly)) continue;
                                                    var nOnly2 = toNumeric(rawOnly);
                                                    if (!isNaN(nOnly2)) {
                                                        bestCol = oc2;
                                                        bestVal = nOnly2;
                                                        break;
                                                    }
                                                }
                                            }
                                            if (bestCol >= 0 && !isNaN(bestVal)) {
                                                out = { 1: bestVal, 2: 0, 3: 0, 4: 0 };
                                                return { values: out, sourceCol: bestCol, mode: 'single', contributingQuarters: [1] };
                                            }
                                        }
                                        var sourceColFreq = {};
                                        var contrib = {};
                                        rows.forEach(function(tr, rowIdx) {
                                            var q = detectQuarterFromGrandTotalRow(tr);
                                            // Fallback: if label has no (Q1)/(Q2) pattern, use row order (row 0 a Q1, row 1 a Q2, a)
                                            if (!q || q < 1 || q > 4) q = rowIdx + 1;
                                            if (!q || q < 1 || q > 4) return;
                                            var tds = window.getRowTdCells(tr);
                                            var vc = getFinalizeQuarterRowValueCol(tr);
                                            if (vc < 0 || vc >= tds.length) return;
                                            var n0 = toNumeric(getCellRawValue(tds[vc]));
                                            if (!isNaN(n0)) {
                                                out[q] = n0;
                                                contrib[q] = true;
                                            }
                                            sourceColFreq[vc] = (sourceColFreq[vc] || 0) + 1;
                                        });
                                        var sourceCol = -1, bestFreq = 0;
                                        Object.keys(sourceColFreq).forEach(function(k) {
                                            var idx = parseInt(k, 10);
                                            var f = sourceColFreq[k];
                                            if (f > bestFreq || (f === bestFreq && idx > sourceCol)) {
                                                bestFreq = f;
                                                sourceCol = idx;
                                            }
                                        });
                                        if (sourceCol >= 0) {
                                            out = { 1: 0, 2: 0, 3: 0, 4: 0 };
                                            contrib = {};
                                            rows.forEach(function(tr2, rowIdx2) {
                                                var q2 = detectQuarterFromGrandTotalRow(tr2);
                                                // Fallback: use row order when label has no (Q1)/(Q2) pattern
                                                if (!q2 || q2 < 1 || q2 > 4) q2 = rowIdx2 + 1;
                                                if (!q2 || q2 < 1 || q2 > 4) return;
                                                var tds2 = window.getRowTdCells(tr2);
                                                if (sourceCol >= tds2.length) return;
                                                var raw2 = String(getCellRawValue(tds2[sourceCol]) || '').trim();
                                                if (!raw2 || raw2 === '-') return;
                                                if (looksLikeQuarterPickerCell(raw2)) return;
                                                var n2 = toNumeric(raw2);
                                                if (!isNaN(n2)) {
                                                    out[q2] = n2;
                                                    contrib[q2] = true;
                                                }
                                            });
                                        }
                                        var cqa = [1, 2, 3, 4].filter(function(q) { return contrib[q]; });
                                        return { values: out, sourceCol: sourceCol, mode: 'quarter', contributingQuarters: cqa };
                                    }
                                    /** Combine Q1aQ4 slots into one overall figure: sum of all four, or average over quarters that had grand-total values. */
                                    function computeFinalizeOverallTotal(payload, overallMode) {
                                        var qVals = payload && payload.values ? payload.values : { 1: 0, 2: 0, 3: 0, 4: 0 };
                                        var sumAll = Number(qVals[1] || 0) + Number(qVals[2] || 0) + Number(qVals[3] || 0) + Number(qVals[4] || 0);
                                        if (payload && payload.mode === 'single') {
                                            return { total: Number(qVals[1] || 0), sumAll: sumAll };
                                        }
                                        if (overallMode === 'avg') {
                                            var cq = payload && Array.isArray(payload.contributingQuarters) ? payload.contributingQuarters : [];
                                            if (cq.length === 0) return { total: 0, sumAll: sumAll };
                                            var s = 0;
                                            for (var i = 0; i < cq.length; i++) {
                                                var qq = cq[i];
                                                s += Number(qVals[qq] || 0);
                                            }
                                            return { total: s / cq.length, sumAll: sumAll };
                                        }
                                        return { total: sumAll, sumAll: sumAll };
                                    }
                                    /** Last resort: first 4 editable columns on the blue row after target quarters (or after col 0). */
                                    function getFinalizeDestColsFromBlueRowDom(blueRow) {
                                        if (!blueRow) return null;
                                        var tds = window.getRowTdCells(blueRow);
                                        if (tds.length < 5) return null;
                                        var map = getPerformanceColumnMap();
                                        var start = 1;
                                        var lastTarget = -1;
                                        [1, 2, 3, 4].forEach(function(q) {
                                            if (map.targetQ[q] > lastTarget) lastTarget = map.targetQ[q];
                                        });
                                        if (lastTarget >= 0) start = lastTarget + 1;
                                        function collect(from) {
                                            var cand = [];
                                            for (var c = from; c < tds.length && c < fields.length; c++) {
                                                if (fieldIndexLooksLikeQuarterField(c)) continue;
                                                if (fieldIndexLooksLikeTargetOnly(c)) continue;
                                                var td = tds[c];
                                                if (!td) continue;
                                                var ftc = (fields[c].type || '');
                                                var hasEditor = td.querySelector && td.querySelector('input, textarea, select');
                                                if (!hasEditor && ftc !== 'number' && !(fields[c].meta && fields[c].meta.calc) && ftc !== 'text' && ftc !== 'textarea') continue;
                                                cand.push(c);
                                                if (cand.length >= 4) break;
                                            }
                                            return cand;
                                        }
                                        var cand = collect(start);
                                        if (cand.length < 4) cand = collect(1);
                                        if (cand.length < 4) return null;
                                        return { 1: cand[0], 2: cand[1], 3: cand[2], 4: cand[3] };
                                    }
                                    function getFinalizeDestinationQuarterCols(sourceCol, blueRow) {
                                        var map = getPerformanceColumnMap();
                                        var cols = { 1: map.accompQ[1], 2: map.accompQ[2], 3: map.accompQ[3], 4: map.accompQ[4] };
                                        var ok = [1, 2, 3, 4].every(function(q) { return cols[q] >= 0; });
                                        if (ok) return cols;
                                        function destOk(colsObj) {
                                            return [1, 2, 3, 4].every(function(q) {
                                                var idx = colsObj[q];
                                                if (idx < 0 || idx >= fields.length) return false;
                                                if (fieldIndexLooksLikeTargetOnly(idx)) return false;
                                                return true;
                                            });
                                        }
                                        function destOkLoose(colsObj) {
                                            return [1, 2, 3, 4].every(function(q) {
                                                var idx = colsObj[q];
                                                if (idx < 0 || idx >= fields.length) return false;
                                                if (fieldIndexLooksLikeQuarterField(idx)) return false;
                                                return true;
                                            });
                                        }
                                        function finalizeScoreBlock(colsObj) {
                                            var s = 0;
                                            [1, 2, 3, 4].forEach(function(q) {
                                                var fi = colsObj[q];
                                                if (fi < 0 || fi >= fields.length) return;
                                                var n = normalizeMetricText(getFieldKey(fields[fi]) + '_' + (fields[fi].label || ''));
                                                if (/accomp|accomplishment|actual|achievement/.test(n)) s += 4;
                                                if (/target/.test(n) && !/accomp|accomplishment|actual/.test(n)) s -= 2;
                                            });
                                            return s;
                                        }
                                        if (map.accompTotal >= 4) {
                                            cols = { 1: map.accompTotal - 4, 2: map.accompTotal - 3, 3: map.accompTotal - 2, 4: map.accompTotal - 1 };
                                            if (destOk(cols)) return cols;
                                            if (destOkLoose(cols)) return cols;
                                        }
                                        if (map.targetTotal >= 0 && map.targetTotal + 4 < fields.length) {
                                            cols = { 1: map.targetTotal + 1, 2: map.targetTotal + 2, 3: map.targetTotal + 3, 4: map.targetTotal + 4 };
                                            if (destOk(cols)) return cols;
                                            if (destOkLoose(cols)) return cols;
                                        }
                                        var lastT = -1;
                                        [1, 2, 3, 4].forEach(function(q) {
                                            if (map.targetQ[q] > lastT) lastT = map.targetQ[q];
                                        });
                                        if (lastT >= 0 && lastT + 4 < fields.length) {
                                            cols = { 1: lastT + 1, 2: lastT + 2, 3: lastT + 3, 4: lastT + 4 };
                                            if (destOk(cols)) return cols;
                                            if (destOkLoose(cols)) return cols;
                                        }
                                        if (sourceCol >= 4) {
                                            cols = { 1: sourceCol - 4, 2: sourceCol - 3, 3: sourceCol - 2, 4: sourceCol - 1 };
                                            if (destOk(cols)) return cols;
                                            if (destOkLoose(cols)) return cols;
                                        }
                                        if (sourceCol >= 0) {
                                            cols = { 1: sourceCol + 1, 2: sourceCol + 2, 3: sourceCol + 3, 4: sourceCol + 4 };
                                            if (destOk(cols)) return cols;
                                            if (destOkLoose(cols)) return cols;
                                        }
                                        var byField = [];
                                        for (var fi = 0; fi < fields.length; fi++) {
                                            if (fieldIndexLooksLikeAccompQuarter(fi)) byField.push(fi);
                                        }
                                        if (byField.length >= 4) {
                                            byField.sort(function(a, b) { return a - b; });
                                            cols = { 1: byField[0], 2: byField[1], 3: byField[2], 4: byField[3] };
                                            if (destOk(cols)) return cols;
                                            if (destOkLoose(cols)) return cols;
                                        }
                                        var quarterFields = [];
                                        for (var fj = 0; fj < fields.length; fj++) {
                                            if (fieldIndexLooksLikeQuarterField(fj)) continue;
                                            var n2 = normalizeMetricText(getFieldKey(fields[fj]) + '_' + (fields[fj].label || ''));
                                            var qx = detectQuarterFromMetric(n2);
                                            if (!qx || qx < 1 || qx > 4) continue;
                                            quarterFields.push({ idx: fj, q: qx });
                                        }
                                        quarterFields.sort(function(a, b) { return a.idx - b.idx; });
                                        var blocks = [];
                                        for (var ia = 0; ia < quarterFields.length; ia++) {
                                            if (quarterFields[ia].q !== 1) continue;
                                            for (var ib = ia + 1; ib < quarterFields.length; ib++) {
                                                if (quarterFields[ib].q !== 2 || quarterFields[ib].idx <= quarterFields[ia].idx) continue;
                                                for (var ic = ib + 1; ic < quarterFields.length; ic++) {
                                                    if (quarterFields[ic].q !== 3 || quarterFields[ic].idx <= quarterFields[ib].idx) continue;
                                                    for (var id = ic + 1; id < quarterFields.length; id++) {
                                                        if (quarterFields[id].q !== 4 || quarterFields[id].idx <= quarterFields[ic].idx) continue;
                                                        var bcols = { 1: quarterFields[ia].idx, 2: quarterFields[ib].idx, 3: quarterFields[ic].idx, 4: quarterFields[id].idx };
                                                        blocks.push({ cols: bcols, score: finalizeScoreBlock(bcols), minIdx: quarterFields[ia].idx });
                                                    }
                                                }
                                            }
                                        }
                                        if (blocks.length > 0) {
                                            blocks.sort(function(x, y) {
                                                if (y.score !== x.score) return y.score - x.score;
                                                return y.minIdx - x.minIdx;
                                            });
                                            for (var bi = 0; bi < blocks.length; bi++) {
                                                if (destOk(blocks[bi].cols)) return blocks[bi].cols;
                                            }
                                            for (var bj = 0; bj < blocks.length; bj++) {
                                                if (finalizeScoreBlock(blocks[bj].cols) > 0 && destOkLoose(blocks[bj].cols)) return blocks[bj].cols;
                                            }
                                            for (var bk = 0; bk < blocks.length; bk++) {
                                                if (destOkLoose(blocks[bk].cols)) return blocks[bk].cols;
                                            }
                                        }
                                        function fieldIndexLooksLikeNonMetricFinalize(idx) {
                                            if (idx < 0 || idx >= fields.length) return true;
                                            var n = normalizeMetricText(getFieldKey(fields[idx]) + '_' + (fields[idx].label || ''));
                                            return /link|url|href|evidence|attachment|remarks|narrative/.test(n);
                                        }
                                        var numFieldIdx = [];
                                        var seenIdx = {};
                                        for (var nk = 1; nk < fields.length; nk++) {
                                            if (fieldIndexLooksLikeQuarterField(nk)) continue;
                                            if (fieldIndexLooksLikeNonMetricFinalize(nk)) continue;
                                            var ftk = (fields[nk].type || '');
                                            var nkw = normalizeMetricText(getFieldKey(fields[nk]) + '_' + (fields[nk].label || ''));
                                            if (ftk === 'number' || (fields[nk].meta && fields[nk].meta.calc)) {
                                                if (!seenIdx[nk]) { numFieldIdx.push(nk); seenIdx[nk] = true; }
                                                continue;
                                            }
                                            var hq = detectQuarterFromMetric(nkw);
                                            if (!hq) continue;
                                            if (fieldIndexLooksLikeTargetOnly(nk)) continue;
                                            if (ftk === 'text' || ftk === 'textarea' || ftk === '' || ftk === 'dropdown' || ftk === 'select') {
                                                if (!seenIdx[nk]) { numFieldIdx.push(nk); seenIdx[nk] = true; }
                                            }
                                        }
                                        numFieldIdx.sort(function(a, b) { return a - b; });
                                        if (numFieldIdx.length >= 4) {
                                            var tail = numFieldIdx.slice(-4);
                                            cols = { 1: tail[0], 2: tail[1], 3: tail[2], 4: tail[3] };
                                            if (destOk(cols)) return cols;
                                            return cols;
                                        }
                                        var domCols = getFinalizeDestColsFromBlueRowDom(blueRow);
                                        if (domCols) return domCols;
                                        return { 1: -1, 2: -1, 3: -1, 4: -1 };
                                    }
                                    function placeKpiFinalizeTotalRowBeforeControls(tr) {
                                        if (!tr || !addGrandTotalRowEl || !addGrandTotalRowEl.parentNode) return;
                                        addGrandTotalRowEl.parentNode.insertBefore(tr, addGrandTotalRowEl);
                                    }
                                    /** Keep the green manual total row directly under the last campus blue summary (above grand totals / buttons). */
                                    function placeManualTotalRowAfterBlueResults(tr) {
                                        if (!tr || !tableBody) return;
                                        var blues = tableBody.querySelectorAll('tr.bg-blue-100:not([data-manual-total-row="1"])');
                                        var lastBlue = blues.length ? blues[blues.length - 1] : null;
                                        if (!lastBlue) {
                                            placeKpiFinalizeTotalRowBeforeControls(tr);
                                            return;
                                        }
                                        lastBlue.insertAdjacentElement('afterend', tr);
                                    }
                                    window.placeManualTotalRowAfterBlueResults = placeManualTotalRowAfterBlueResults;
                                    function ensureKpiFinalizeTotalRow() {
                                        if (!tableBody || !kpiFinalizeTotalTemplate || !addGrandTotalRowEl) return null;
                                        var existing = tableBody.querySelector('tr.kpi-finalize-total-row:not(#kpi-finalize-total-row-template)');
                                        if (existing) {
                                            placeKpiFinalizeTotalRowBeforeControls(existing);
                                            return existing;
                                        }
                                        var clone = kpiFinalizeTotalTemplate.cloneNode(true);
                                        clone.id = '';
                                        clone.removeAttribute('id');
                                        clone.classList.remove('hidden');
                                        clone.querySelectorAll('input').forEach(function(inp) { inp.value = ''; });
                                        placeKpiFinalizeTotalRowBeforeControls(clone);
                                        return clone;
                                    }
                                    if (addGrandTotalBtn && addGrandTotalRowEl && grandTotalTemplate) {
                                        addGrandTotalBtn.addEventListener('click', function(e) {
                                            e.preventDefault();
                                            e.stopPropagation();
                                            var existing = tableBody.querySelectorAll('tr.grand-total-row:not(#grand-total-row-template)');
                                            var clone = grandTotalTemplate.cloneNode(true);
                                            clone.id = '';
                                            clone.removeAttribute('id');
                                            clone.classList.remove('hidden');
                                            var scheme = grandTotalColorSchemes[existing.length % grandTotalColorSchemes.length];
                                            applyGrandTotalColor(clone, scheme);
                                            clone.querySelectorAll('input').forEach(function(inp) { inp.value = ''; });
                                            var labelSpan = clone.querySelector('td:first-child span');
                                            if (labelSpan) labelSpan.textContent = 'Grand total' + (existing.length > 0 ? ' ' + (existing.length + 1) : '');
                                            addGrandTotalRowEl.parentNode.insertBefore(clone, addGrandTotalRowEl);
                                            window.tableDataDirty = true;
                                            if (typeof scheduleAutoSave === 'function') scheduleAutoSave();
                                        });
                                    }
                                    var addTotalRowsBtn = document.getElementById('add-total-rows-btn');
                                    if (addTotalRowsBtn && addGrandTotalRowEl && manualTotalEmptyRowTemplate) {
                                        addTotalRowsBtn.addEventListener('click', function(e) {
                                            e.preventDefault();
                                            e.stopPropagation();
                                            var existingManual = tableBody.querySelector('tr[data-manual-total-row="1"]:not(#manual-total-empty-row-template)');
                                            if (existingManual) {
                                                if (typeof window.showToast === 'function') window.showToast('notice', 'Only one manual total row is allowed.');
                                                try { existingManual.scrollIntoView({ block: 'nearest', behavior: 'smooth' }); } catch (eScroll) {}
                                                return;
                                            }
                                            var clone = manualTotalEmptyRowTemplate.cloneNode(true);
                                            clone.removeAttribute('id');
                                            clone.removeAttribute('aria-hidden');
                                            clone.classList.remove('hidden');
                                            clone.classList.add('data-row', 'bg-emerald-100', 'group', 'border-l-4', 'border-emerald-500');
                                            var keys = pickLastSectionKeysForManualTotalRow();
                                            clone.setAttribute('data-submission-id', keys.submissionId);
                                            clone.setAttribute('data-user-id', keys.userId);
                                            clone.querySelectorAll('input').forEach(function(inp) { inp.value = ''; });
                                            clone.querySelectorAll('span').forEach(function(sp) { if (sp.closest && sp.closest('td') && sp.closest('td').getAttribute('data-field-col') === '0') sp.textContent = ''; });
                                            placeManualTotalRowAfterBlueResults(clone);
                                            window.tableDataDirty = true;
                                            if (typeof scheduleAutoSave === 'function') scheduleAutoSave();
                                        });
                                    }
                                    var finalizeChoicePopover = document.getElementById('finalize-choice-popover');
                                    var finalizeChoiceSumBtn = document.getElementById('finalize-choice-sum-btn');
                                    var finalizeChoiceAvgBtn = document.getElementById('finalize-choice-avg-btn');
                                    var finalizeChoiceCancelBtn = document.getElementById('finalize-choice-cancel-btn');
                                    var finalizeChoiceCloseBtn = document.getElementById('finalize-choice-close-btn');
                                    var finalizeChoiceOutsideBound = null;
                                    var finalizeChoiceRepositionHandler = null;
                                    function isFinalizeChoicePopoverOpen() {
                                        return finalizeChoicePopover && !finalizeChoicePopover.classList.contains('hidden');
                                    }
                                    function positionFinalizeChoicePopover() {
                                        if (!finalizeChoicePopover || !finalizeKpiBtn || finalizeChoicePopover.classList.contains('hidden')) return;
                                        var btn = finalizeKpiBtn.getBoundingClientRect();
                                        var pop = finalizeChoicePopover;
                                        var vw = window.innerWidth || document.documentElement.clientWidth;
                                        var vh = window.innerHeight || document.documentElement.clientHeight;
                                        var gap = 8;
                                        var pw = pop.offsetWidth || 288;
                                        var ph = pop.offsetHeight || 220;
                                        var left = btn.left + (btn.width / 2) - (pw / 2);
                                        left = Math.max(gap, Math.min(vw - pw - gap, left));
                                        var belowTop = btn.bottom + gap;
                                        var aboveTop = btn.top - gap - ph;
                                        var top = belowTop;
                                        if (belowTop + ph > vh - gap && aboveTop >= gap) top = aboveTop;
                                        top = Math.max(gap, Math.min(vh - ph - gap, top));
                                        pop.style.left = left + 'px';
                                        pop.style.top = top + 'px';
                                    }
                                    function closeFinalizeChoicePopover() {
                                        if (!finalizeChoicePopover) return;
                                        finalizeChoicePopover.classList.add('hidden');
                                        finalizeChoicePopover.setAttribute('aria-hidden', 'true');
                                        document.removeEventListener('keydown', finalizeChoiceOnEscape);
                                        if (finalizeChoiceOutsideBound) {
                                            document.removeEventListener('mousedown', finalizeChoiceOutsideBound, true);
                                            finalizeChoiceOutsideBound = null;
                                        }
                                        if (finalizeChoiceRepositionHandler) {
                                            window.removeEventListener('resize', finalizeChoiceRepositionHandler);
                                            window.removeEventListener('scroll', finalizeChoiceRepositionHandler, true);
                                            finalizeChoiceRepositionHandler = null;
                                        }
                                    }
                                    function finalizeChoiceOnEscape(ev) {
                                        if (ev.key === 'Escape') closeFinalizeChoicePopover();
                                    }
                                    function openFinalizeChoicePopover() {
                                        if (!finalizeChoicePopover || !finalizeKpiBtn) return;
                                        finalizeChoicePopover.classList.remove('hidden');
                                        finalizeChoicePopover.setAttribute('aria-hidden', 'false');
                                        document.addEventListener('keydown', finalizeChoiceOnEscape);
                                        finalizeChoiceOutsideBound = function(ev) {
                                            if (!finalizeChoicePopover || finalizeChoicePopover.classList.contains('hidden')) return;
                                            if (finalizeChoicePopover.contains(ev.target)) return;
                                            if (finalizeKpiBtn && finalizeKpiBtn.contains(ev.target)) return;
                                            closeFinalizeChoicePopover();
                                        };
                                        document.addEventListener('mousedown', finalizeChoiceOutsideBound, true);
                                        finalizeChoiceRepositionHandler = function() {
                                            positionFinalizeChoicePopover();
                                        };
                                        window.addEventListener('resize', finalizeChoiceRepositionHandler);
                                        window.addEventListener('scroll', finalizeChoiceRepositionHandler, true);
                                        function runPos() {
                                            positionFinalizeChoicePopover();
                                            requestAnimationFrame(positionFinalizeChoicePopover);
                                        }
                                        requestAnimationFrame(runPos);
                                        if (finalizeChoiceSumBtn) finalizeChoiceSumBtn.focus();
                                    }
                                    function runFinalizeWithOverallMode(overallMode) {
                                        if (!tableBody) return;
                                        var payload = getFinalizeQuarterTotals();
                                        var qVals = payload.values || { 1: 0, 2: 0, 3: 0, 4: 0 };
                                        var agg = computeFinalizeOverallTotal(payload, overallMode);
                                        var total = agg.total;
                                        var perfMap = getPerformanceColumnMap();
                                        var finRow = ensureKpiFinalizeTotalRow();
                                        var wrote = false;
                                        var totalWriteCol = -1;
                                        if (finRow) {
                                            var finTds = window.getRowTdCells(finRow);
                                            for (var ci = 1; ci < finTds.length; ci++) {
                                                var inp = finTds[ci].querySelector('input, textarea');
                                                if (inp) inp.value = '';
                                            }
                                            totalWriteCol = (payload.sourceCol >= 0) ? payload.sourceCol : (perfMap.accompTotal >= 0 ? perfMap.accompTotal : -1);
                                            var finalizeAsPct = finalizeColumnIsPercentage(totalWriteCol);
                                            if (totalWriteCol >= 0 && totalWriteCol < finTds.length) {
                                                setCellRawValue(finTds[totalWriteCol], formatFinalizeOverallDisplayValue(total, finalizeAsPct));
                                                wrote = true;
                                            }
                                            finRow.setAttribute('data-finalized-accomp-q1', Number(qVals[1] || 0).toFixed(2));
                                            finRow.setAttribute('data-finalized-accomp-q2', Number(qVals[2] || 0).toFixed(2));
                                            finRow.setAttribute('data-finalized-accomp-q3', Number(qVals[3] || 0).toFixed(2));
                                            finRow.setAttribute('data-finalized-accomp-q4', Number(qVals[4] || 0).toFixed(2));
                                            finRow.setAttribute('data-finalize-overall-mode', overallMode);
                                        }
                                        if (!wrote) {
                                            if (typeof window.showToast === 'function') window.showToast('notice', 'Finalize: unable to detect quarter destination columns.');
                                            return;
                                        }
                                        // Finalize must stamp Q1aQ4 on each blue summary row: collectBySubmission skips the purple KPI row,
                                        // but _meta.finalized_accomp is read from tr.bg-blue-100 rows for VPASS / Form Details accomplishment.
                                        (function pushFinalizeQuarterTotalsToBlueRows() {
                                            var blueRowsList = tableBody.querySelectorAll('tr.bg-blue-100');
                                            var fmtMeta = function(n) { return Number(n || 0).toFixed(2); };
                                            for (var bri = 0; bri < blueRowsList.length; bri++) {
                                                var brow0 = blueRowsList[bri];
                                                brow0.setAttribute('data-finalized-accomp-q1', fmtMeta(qVals[1]));
                                                brow0.setAttribute('data-finalized-accomp-q2', fmtMeta(qVals[2]));
                                                brow0.setAttribute('data-finalized-accomp-q3', fmtMeta(qVals[3]));
                                                brow0.setAttribute('data-finalized-accomp-q4', fmtMeta(qVals[4]));
                                            }
                                            var sampleBlue = blueRowsList.length ? blueRowsList[0] : null;
                                            if (!sampleBlue) return;
                                            var destCols = getFinalizeDestinationQuarterCols(payload.sourceCol, sampleBlue);
                                            if (!destCols || ![1, 2, 3, 4].every(function(q) { return destCols[q] >= 0; })) return;
                                            for (var brj = 0; brj < blueRowsList.length; brj++) {
                                                var brow = blueRowsList[brj];
                                                var btds = window.getRowTdCells(brow);
                                                for (var qx = 1; qx <= 4; qx++) {
                                                    var ci = destCols[qx];
                                                    if (ci < 0 || ci >= btds.length) continue;
                                                    var disp = formatFinalizeOverallDisplayValue(Number(qVals[qx] || 0), finalizeColumnIsPercentage(ci));
                                                    setCellRawValue(btds[ci], disp);
                                                    if (typeof setManualOverrideCell === 'function') setManualOverrideCell(btds[ci], true);
                                                }
                                                if (typeof recomputeBlueRowPerformance === 'function') {
                                                    try { recomputeBlueRowPerformance(brow); } catch (eBr) {}
                                                }
                                                if (perfMap.accompTotal >= 0 && perfMap.accompTotal < btds.length && typeof setManualOverrideCell === 'function') {
                                                    setManualOverrideCell(btds[perfMap.accompTotal], true);
                                                }
                                            }
                                        })();

                                        window.tableDataDirty = true;
                                        // Persist immediately so template.fields_json.finalized_accomp + submissions save without waiting for autosave delay.
                                        setTimeout(function() {
                                            if (typeof window.performSaveTableData === 'function') {
                                                window.performSaveTableData({
                                                    onSuccess: function() {
                                                        if (typeof setAutosaveStatus === 'function') setAutosaveStatus('saved');
                                                    }
                                                });
                                            } else if (typeof scheduleAutoSave === 'function') {
                                                scheduleAutoSave();
                                            }
                                        }, 0);
                                        var modeLabel = overallMode === 'avg' ? 'average' : 'sum';
                                        if (typeof window.showToast === 'function') window.showToast('notice', 'Finalized: overall ' + modeLabel + ' (' + formatFinalizeOverallDisplayValue(total, finalizeColumnIsPercentage(totalWriteCol)) + ') written to Overall total row.');
                                    }
                                    if (finalizeKpiBtn) {
                                        finalizeKpiBtn.addEventListener('click', function(e) {
                                            e.preventDefault();
                                            e.stopPropagation();
                                            var grandTotalRows = Array.prototype.slice.call(tableBody.querySelectorAll('tr.grand-total-row:not(#grand-total-row-template)'));
                                            if (grandTotalRows.length === 0) {
                                                if (typeof window.showToast === 'function') window.showToast('notice', 'No grand total rows found. Add grand total rows first.');
                                                return;
                                            }
                                            if (!finalizeChoicePopover) {
                                                runFinalizeWithOverallMode('sum');
                                                return;
                                            }
                                            if (isFinalizeChoicePopoverOpen()) {
                                                closeFinalizeChoicePopover();
                                                return;
                                            }
                                            openFinalizeChoicePopover();
                                        });
                                    }
                                    if (finalizeChoiceCloseBtn) finalizeChoiceCloseBtn.addEventListener('click', closeFinalizeChoicePopover);
                                    if (finalizeChoiceCancelBtn) finalizeChoiceCancelBtn.addEventListener('click', closeFinalizeChoicePopover);
                                    if (finalizeChoiceSumBtn) finalizeChoiceSumBtn.addEventListener('click', function() {
                                        closeFinalizeChoicePopover();
                                        runFinalizeWithOverallMode('sum');
                                    });
                                    if (finalizeChoiceAvgBtn) finalizeChoiceAvgBtn.addEventListener('click', function() {
                                        closeFinalizeChoicePopover();
                                        runFinalizeWithOverallMode('avg');
                                    });
                                    // Restore saved grand total / finalize rows on page load.
                                    // Make this idempotent: if rows already exist in DOM (from server-rendered table_data),
                                    // clear non-template instances first so refresh does not duplicate them.
                                    if (tableBody) {
                                        var existingGrandTotals = tableBody.querySelectorAll('tr.grand-total-row:not(#grand-total-row-template)');
                                        existingGrandTotals.forEach(function(tr) { tr.remove(); });
                                        var existingFinalizeRows = tableBody.querySelectorAll('tr.kpi-finalize-total-row:not(#kpi-finalize-total-row-template)');
                                        existingFinalizeRows.forEach(function(tr) { tr.remove(); });
                                        var existingManualTotals = tableBody.querySelectorAll('tr[data-manual-total-row="1"]:not(#manual-total-empty-row-template)');
                                        existingManualTotals.forEach(function(tr) { tr.remove(); });
                                    }
                                    // Restore saved grand total rows on page load
                                    if (savedGrandTotalRows && Array.isArray(savedGrandTotalRows) && savedGrandTotalRows.length > 0 && grandTotalTemplate && addGrandTotalRowEl) {
                                        var schemeNames = ['amber', 'emerald', 'teal', 'sky', 'violet', 'rose'];
                                        savedGrandTotalRows.forEach(function(saved, idx) {
                                            var clone = grandTotalTemplate.cloneNode(true);
                                            clone.id = '';
                                            clone.removeAttribute('id');
                                            clone.classList.remove('hidden');
                                            var schemeIdx = idx % grandTotalColorSchemes.length;
                                            var scheme = grandTotalColorSchemes[schemeIdx];
                                            applyGrandTotalColor(clone, scheme);
                                            var rowData = saved.row || {};
                                            var cellMappings = saved.cell_mappings || {};
                                            window.getRowTdCells(clone).forEach(function(td, c) {
                                                if (c >= fields.length) return;
                                                var keyF = getFieldKey(fields[c]);
                                                var val = rowData[keyF];
                                                if (val != null && val !== '') {
                                                    var input = td.querySelector('input, select, textarea');
                                                    var span = td.querySelector('span');
                                                    if (input) input.value = val;
                                                    else if (span) span.textContent = val;
                                                }
                                                var mapping = cellMappings[keyF];
                                                if (mapping && td) {
                                                    var m = { ui_calc_type: mapping.ui_calc_type || 'grand-total', sourceA: mapping.sourceA, sourceB: mapping.sourceB, ui_formula_operation: mapping.ui_formula_operation, source_quarter: mapping.source_quarter, grand_total_ctc_aggregate: mapping.grand_total_ctc_aggregate };
                                                    if (Array.isArray(mapping.row_uids) && mapping.row_uids.length > 0) m.row_uids = mapping.row_uids;
                                                    if (Array.isArray(mapping.row_indices) && mapping.row_indices.length > 0) m.row_indices = mapping.row_indices;
                                                    if (mapping.count_adjust !== undefined && mapping.count_adjust !== null) m.count_adjust = mapping.count_adjust;
                                                    setCellFormulaMapping(td, m);
                                                }
                                            });
                                            var labelSpan = clone.querySelector('td:first-child span');
                                            if (labelSpan) labelSpan.textContent = saved.label || ('Grand total' + (idx > 0 ? ' ' + (idx + 1) : ''));
                                            addGrandTotalRowEl.parentNode.insertBefore(clone, addGrandTotalRowEl);
                                        });
                                    }
                                    function applyManualTotalCellMappingFromSaved(td, mapping) {
                                        if (!td || !mapping) return;
                                        var m = {
                                            ui_calc_type: mapping.ui_calc_type || '',
                                            ui_formula_operation: mapping.ui_formula_operation || mapping.operation || '',
                                            sourceA: mapping.sourceA,
                                            sourceB: mapping.sourceB,
                                            section_ref: mapping.section_ref
                                        };
                                        if (Array.isArray(mapping.source_columns) && mapping.source_columns.length > 0) m.source_columns = mapping.source_columns.slice();
                                        if (Array.isArray(mapping.source_keys) && mapping.source_keys.length > 0) m.source_keys = mapping.source_keys.slice();
                                        if (Array.isArray(mapping.row_uids) && mapping.row_uids.length > 0) m.row_uids = mapping.row_uids.slice();
                                        if (Array.isArray(mapping.row_indices) && mapping.row_indices.length > 0) m.row_indices = mapping.row_indices.slice();
                                        if (mapping.count_adjust !== undefined && mapping.count_adjust !== null) m.count_adjust = mapping.count_adjust;
                                        if (mapping.custom_expr) m.custom_expr = mapping.custom_expr;
                                        if (mapping.manual_total_from_all_blues === true || String(mapping.manual_total_from_all_blues || '') === '1') {
                                            m.manual_total_from_all_blues = true;
                                        }
                                        setCellFormulaMapping(td, m);
                                    }
                                    if (savedManualTotalRow && savedManualTotalRow.row && manualTotalEmptyRowTemplate && addGrandTotalRowEl) {
                                        var mClone = manualTotalEmptyRowTemplate.cloneNode(true);
                                        mClone.removeAttribute('id');
                                        mClone.removeAttribute('aria-hidden');
                                        mClone.classList.remove('hidden');
                                        mClone.classList.add('data-row', 'bg-emerald-100', 'group', 'border-l-4', 'border-emerald-500');
                                        mClone.setAttribute('data-manual-total-row', '1');
                                        mClone.setAttribute('data-row-type', 'summary');
                                        var mRowData = savedManualTotalRow.row || {};
                                        var mCellMappings = savedManualTotalRow.cell_mappings || {};
                                        window.getRowTdCells(mClone).forEach(function(td, c) {
                                            if (c >= fields.length) return;
                                            var keyF = getFieldKey(fields[c]);
                                            var val = mRowData[keyF];
                                            if (val != null && val !== '') {
                                                var input = td.querySelector('input, select, textarea');
                                                var span = td.querySelector('span');
                                                if (input) input.value = val;
                                                else if (span) span.textContent = val;
                                            }
                                            var mapping = mCellMappings[keyF];
                                            if (mapping) applyManualTotalCellMappingFromSaved(td, mapping);
                                        });
                                        placeManualTotalRowAfterBlueResults(mClone);
                                        if (typeof recomputeBlueRowFormulasInSection === 'function') {
                                            try { recomputeBlueRowFormulasInSection([mClone]); } catch (eManRe) {}
                                        }
                                        if (typeof recomputeBlueRowPerformance === 'function') {
                                            try { recomputeBlueRowPerformance(mClone); } catch (eManPerf) {}
                                        }
                                    }
                                    if (savedKpiFinalizeTotalRow && savedKpiFinalizeTotalRow.row && kpiFinalizeTotalTemplate && addGrandTotalRowEl) {
                                        var kClone = kpiFinalizeTotalTemplate.cloneNode(true);
                                        kClone.id = '';
                                        kClone.removeAttribute('id');
                                        kClone.classList.remove('hidden');
                                        var kRowData = savedKpiFinalizeTotalRow.row || {};
                                        window.getRowTdCells(kClone).forEach(function(td, c) {
                                            if (c >= fields.length) return;
                                            var keyF = getFieldKey(fields[c]);
                                            var val = kRowData[keyF];
                                            if (val != null && val !== '') {
                                                var input = td.querySelector('input, select, textarea');
                                                var span = td.querySelector('span');
                                                if (input) {
                                                    var rawK = String(val).trim();
                                                    var numK = typeof toNumeric === 'function' ? toNumeric(rawK) : NaN;
                                                    if (rawK !== '' && rawK !== '-' && isFinite(numK)) {
                                                        var pctK = finalizeColumnIsPercentage(c);
                                                        input.value = formatFinalizeOverallDisplayValue(numK, pctK);
                                                    } else input.value = rawK;
                                                } else if (span) span.textContent = val;
                                            }
                                        });
                                        var kLabel = kClone.querySelector('td:first-child span');
                                        if (kLabel && savedKpiFinalizeTotalRow.label) kLabel.textContent = savedKpiFinalizeTotalRow.label;
                                        var savedOm = savedKpiFinalizeTotalRow.overall_mode === 'avg' ? 'avg' : 'sum';
                                        kClone.setAttribute('data-finalize-overall-mode', savedOm);
                                        placeKpiFinalizeTotalRowBeforeControls(kClone);
                                    }
                                    var removedDupBlueRows = dedupeAdjacentBlueSummaryRowsOnLoad();
                                    if (removedDupBlueRows > 0) {
                                        window.tableDataDirty = true;
                                        if (typeof setAutosaveStatus === 'function') setAutosaveStatus('saving');
                                        if (typeof window.performSaveTableData === 'function') {
                                            var doSaveDupCleanup = function() {
                                                window.performSaveTableData({
                                                    onSuccess: function() {
                                                        if (typeof setAutosaveStatus === 'function') setAutosaveStatus('saved');
                                                    }
                                                });
                                            };
                                            if (typeof requestAnimationFrame === 'function') requestAnimationFrame(function() { doSaveDupCleanup(); });
                                            else setTimeout(doSaveDupCleanup, 0);
                                        } else if (typeof scheduleAutoSave === 'function') {
                                            scheduleAutoSave();
                                        }
                                    }
                                    function flushDeleteChangesImmediately() {
                                        window.tableDataDirty = true;
                                        if (typeof setAutosaveStatus === 'function') setAutosaveStatus('saving');
                                        if (typeof window.performSaveTableData === 'function') {
                                            var doSaveDelete = function() {
                                                window.performSaveTableData({
                                                    onSuccess: function() {
                                                        if (typeof setAutosaveStatus === 'function') setAutosaveStatus('saved');
                                                    }
                                                });
                                            };
                                            if (typeof requestAnimationFrame === 'function') requestAnimationFrame(function() { doSaveDelete(); });
                                            else setTimeout(doSaveDelete, 0);
                                        } else if (typeof scheduleAutoSave === 'function') {
                                            scheduleAutoSave();
                                        }
                                    }
                                    // Keep delete button behaviour on click
                                    tableBody.addEventListener('click', function(e) {
                                        if (suppressNextTableClickAfterReselectCellsPick) {
                                            suppressNextTableClickAfterReselectCellsPick = false;
                                            e.preventDefault();
                                            e.stopImmediatePropagation();
                                            return;
                                        }
                                        if (e.target.closest('#add-grand-total-btn') || e.target.closest('#add-total-rows-btn') || e.target.closest('#finalize-kpi-btn')) return;
                                        var removeGTBtn = e.target.closest('.remove-grand-total-btn');
                                        if (removeGTBtn) {
                                            var trGT = removeGTBtn.closest('tr.grand-total-row');
                                            if (trGT && !trGT.id) {
                                                trGT.remove();
                                                flushDeleteChangesImmediately();
                                            }
                                            return;
                                        }
                                        var removeKpiBtn = e.target.closest('.remove-kpi-finalize-total-btn');
                                        if (removeKpiBtn) {
                                            var trKpi = removeKpiBtn.closest('tr.kpi-finalize-total-row');
                                            if (trKpi && !trKpi.id) {
                                                trKpi.remove();
                                                flushDeleteChangesImmediately();
                                            }
                                            return;
                                        }
                                        // Check delete button FIRST so it works on blue rows (blue row cell logic would otherwise intercept)
                                        var btn = e.target.closest('.delete-row-btn');
                                        if (btn) {
                                            var trDel = btn.closest('tr.data-row');
                                            if (trDel) {
                                                var prev = trDel.previousElementSibling;
                                                var next = trDel.nextElementSibling;
                                                var wasBlueRow = trDel.classList.contains('bg-blue-100');
                                                trDel.remove();
                                                if (prev && prev.classList.contains('separator-row')) prev.remove();
                                                if (wasBlueRow && next && next.classList.contains('separator-row')) next.remove();
                                                // When deleting a DATA row: if next is a blue summary row and this was the only data row, remove the orphan blue row too

                                                
                                                if (!wasBlueRow && next && next.classList.contains('bg-blue-100')) {
                                                    var onlyDataInGroup = !prev || prev.classList.contains('section-header-row') || prev.classList.contains('separator-row') || prev.classList.contains('bg-blue-100');
                                                    if (onlyDataInGroup) next.remove();
                                                }
                                            }
                                            updateDeleteBtnMulti();
                                            flushDeleteChangesImmediately();
                                            return;
                                        }
                                        // If clicking directly on an input/select/textarea, let it be edited normally
                                        if (e.target.closest('input, select, textarea')) {
                                            var trInput = e.target.closest('tr.data-row');
                                            var tdInput = e.target.closest('td');
                                            if (trInput && tdInput && (trInput.classList.contains('bg-blue-100') || trInput.classList.contains('grand-total-row'))) {
                                                // Deterministic behavior: clicking a blue/grand-total result cell always
                                                // shows that cell's own saved sources.
                                                autoSelectSourcesForBlueCell(tdInput, { silent: true, preferFullSection: true });
                                            }
                                            return;
                                        }
                                        var trBlue = e.target.closest('tr.data-row');
                                        var tdBlue = e.target.closest('td');
                                        if (trBlue && tdBlue && (trBlue.classList.contains('bg-blue-100') || trBlue.classList.contains('grand-total-row'))) {
                                            var hasGrandTotalSelected = tableBody && Array.prototype.slice.call(tableBody.querySelectorAll('td.cell-selected')).some(function(t) {
                                                var r = t.closest('tr.data-row');
                                                return r && r.classList.contains('grand-total-row');
                                            });
                                            var alreadySelectedBlue = tdBlue.classList.contains('cell-selected');
                                            if (hasGrandTotalSelected && trBlue.classList.contains('bg-blue-100')) {
                                                var selectedGTForQuarter = tableBody ? Array.prototype.slice.call(tableBody.querySelectorAll('td.cell-selected')).find(function(t) {
                                                    var r = t.closest('tr.data-row');
                                                    return r && r.classList.contains('grand-total-row');
                                                }) : null;
                                                var gtTargetColIdx = selectedGTForQuarter ? getColIndex(selectedGTForQuarter) : -1;
                                                var blueScopeGt = typeof getGrandTotalSchoolYearBlueScopeKey === 'function' ? getGrandTotalSchoolYearBlueScopeKey(selectedGTForQuarter || null) : null;
                                                if (!isGrandTotalManualSelectionMode(selectedGTForQuarter) && blueScopeGt && typeof findGrandTotalSchoolYearScopedBlueSourceCells === 'function') {
                                                    var allowedBlue = findGrandTotalSchoolYearScopedBlueSourceCells(selectedGTForQuarter, blueScopeGt);
                                                    var isAllowedBlue = allowedBlue.some(function(x) { return x === tdBlue; });
                                                    if (!isAllowedBlue) {
                                                        if (typeof window.showToast === 'function') window.showToast('notice', 'Select only blue summary cells that match this grand total scope (same semester / school year).');
                                                        return;
                                                    }
                                                }
                                                var requiredQuarter = resolveGrandTotalQuarter(gtTargetColIdx, selectedGTForQuarter || null);
                                                if (!isGrandTotalManualSelectionMode(selectedGTForQuarter) && requiredQuarter && detectQuarterFromRow(trBlue) !== requiredQuarter) {
                                                    if (typeof window.showToast === 'function') window.showToast('notice', 'Select only Q' + requiredQuarter + ' blue source cells for this grand total.');
                                                    return;
                                                }
                                                if (alreadySelectedBlue) {
                                                    setCellSelected(trBlue, tdBlue, false);
                                                } else if (e.ctrlKey || e.metaKey) {
                                                    setCellSelected(trBlue, tdBlue, true);
                                                } else {
                                                    autoSelectSourcesForBlueCell(tdBlue, { silent: true, preferFullSection: true });
                                                    return;
                                                }
                                                setGrandTotalManualOverride(selectedGTForQuarter, true);
                                                setSelectionModeState('Using your current manual selection.');
                                                updateFormulaButtonState();
                                                return;
                                            }
                                            var statsBlue = getSelectedCellsStats();
                                            // Deterministic behavior: clicking a blue result cell always retrieves
                                            // and highlights that clicked cell's source selection.
                                            autoSelectSourcesForBlueCell(tdBlue, { silent: true, preferFullSection: true });
                                            return;
                                        }
                                    });

                                    // Row-actions popover: show on row hover (and on cell click), hide when leaving row/popover
                                    if (tableBody && rowActionsPopover) {
                                        var rowActionsAddBtn = document.getElementById('row-actions-add-btn');
                                        var rowActionsAddRowsBtn = document.getElementById('row-actions-add-rows-btn');
                                        var rowActionsCalculateBtn = document.getElementById('row-actions-calculate-btn');
                                        var rowActionsSeparateBtn = document.getElementById('row-actions-separate-btn');
                                        var rowActionsOpenCalcBtnBlue = document.getElementById('row-actions-open-calc-btn-blue');
                                        var rowActionsRemoveFormulaBtnBlue = document.getElementById('row-actions-remove-formula-btn-blue');
                                        if (rowActionsAddBtn) rowActionsAddBtn.addEventListener('click', function(e) {
                                            e.preventDefault();
                                            e.stopPropagation();
                                            var row = hoveredRowForActions;
                                            hideRowActionsPopover();
                                            if (row) addNewRowMulti(row);
                                        });
                                        if (rowActionsAddRowsBtn) rowActionsAddRowsBtn.addEventListener('click', function(e) {
                                            e.preventDefault();
                                            e.stopPropagation();
                                            var row = hoveredRowForActions;
                                            hideRowActionsPopover();
                                            if (row) {
                                                showRowCountDialog(1).then(function(count) {
                                                    if (count === null) return;
                                                    addMultipleNewRowsMulti(row, count);
                                                });
                                            }
                                        });
                                        if (rowActionsCalculateBtn) rowActionsCalculateBtn.addEventListener('click', function(e) {
                                            e.preventDefault();
                                            e.stopPropagation();
                                            var row = hoveredRowForActions;
                                            hideRowActionsPopover();
                                            if (row) applyRowTotalsFromYearColumns(row);
                                        });
                                        if (rowActionsSeparateBtn) rowActionsSeparateBtn.addEventListener('click', function(e) {
                                            e.preventDefault();
                                            e.stopPropagation();
                                            var row = hoveredRowForActions;
                                            hideRowActionsPopover();
                                            if (row) addSeparateRowMulti(row);
                                        });
                                        var rowActionsAddBtnBlue = document.getElementById('row-actions-add-btn-blue');
                                        if (rowActionsAddBtnBlue) rowActionsAddBtnBlue.addEventListener('click', function(e) {
                                            e.preventDefault();
                                            e.stopPropagation();
                                            var row = hoveredRowForActions;
                                            hideRowActionsPopover();
                                            if (row) addNewRowMulti(row);
                                        });
                                        if (rowActionsOpenCalcBtnBlue) rowActionsOpenCalcBtnBlue.addEventListener('click', function(e) {
                                            e.preventDefault();
                                            e.stopPropagation();
                                            var rowOpen = hoveredRowForActions;
                                            try {
                                                window._uapsManualTotalCrossBluesApply = !!(rowOpen && String(rowOpen.getAttribute('data-manual-total-row') || '') === '1');
                                            } catch (eMc1) {}
                                            forceOpenSelectionPopoverOnce = true;
                                            hideRowActionsPopover();
                                            updateFormulaButtonState();
                                        });
                                        if (rowActionsRemoveFormulaBtnBlue) rowActionsRemoveFormulaBtnBlue.addEventListener('click', function(e) {
                                            e.preventDefault();
                                            e.stopPropagation();
                                            var targetTdRf = rowActionsBlueTargetRefCell || lastClickedCellMulti;
                                            var targetRowRf = targetTdRf && targetTdRf.closest('tr.data-row');
                                            if (targetTdRf && targetRowRf && (targetRowRf.classList.contains('bg-blue-100') || targetRowRf.classList.contains('grand-total-row'))) {
                                                clearSelectionMulti();
                                                setCellSelected(targetRowRf, targetTdRf, true);
                                                lastClickedRowMulti = targetRowRf;
                                                lastClickedCellMulti = targetTdRf;
                                            }
                                            hideRowActionsPopover();
                                            applyRemoveFormula();
                                        });
                                        var rowActionsReselectCellsBlueBtn = document.getElementById('row-actions-reselect-cells-blue-btn');
                                        if (rowActionsReselectCellsBlueBtn) rowActionsReselectCellsBlueBtn.addEventListener('click', function(e) {
                                            e.preventDefault();
                                            e.stopPropagation();
                                            var targetTd = rowActionsBlueTargetRefCell || lastClickedCellMulti;
                                            var trT = targetTd && targetTd.closest('tr.data-row');
                                            if (!targetTd || !trT || !trT.classList.contains('bg-blue-100')) {
                                                if (typeof window.showToast === 'function') window.showToast('notice', 'Open a on the blue total you want to update, then tap Reselect Cells.');
                                                return;
                                            }
                                            reselectCellsTargetTd = targetTd;
                                            hideRowActionsPopover();
                                            setTimeout(function() {
                                                pendingReselectCellsPick = true;
                                                if (document.body) document.body.classList.add('uaps-reselect-cells-pick');
                                                if (typeof window.showToast === 'function') window.showToast('notice', 'Click another blue total to copy only its row selection (not its formula). Esc to cancel.', { tag: 'reselect-cells-pick' });
                                            }, 0);
                                        });
                                        function isElementUnderRowActionsChrome(el) {
                                            if (!el || !el.nodeType) return false;
                                            try {
                                                if (rowActionsPopover && rowActionsPopover.contains(el)) return true;
                                                if (rowActionsPopoverBlue && rowActionsPopoverBlue.contains(el)) return true;
                                                if (rowActionsDotsBtn && rowActionsDotsBtn.contains(el)) return true;
                                                if (selectionPopover && selectionPopover.contains(el)) return true;
                                            } catch (err) {}
                                            return false;
                                        }
                                        var ROW_ACTIONS_HIDE_MS = 280;
                                        tableBody.addEventListener('mouseover', function(e) {
                                            if (rowActionsHideTimeout) { clearTimeout(rowActionsHideTimeout); rowActionsHideTimeout = null; }
                                        });
                                        if (rowActionsPopoverBlue) {
                                            rowActionsPopoverBlue.addEventListener('mouseover', function() {
                                                if (rowActionsHideTimeout) { clearTimeout(rowActionsHideTimeout); rowActionsHideTimeout = null; }
                                            });
                                            rowActionsPopoverBlue.addEventListener('mouseout', function(e) {
                                                var related = e.relatedTarget;
                                                if (related && rowActionsPopoverBlue.contains(related)) return;
                                                if (isElementUnderRowActionsChrome(related)) return;
                                                if (related && hoveredRowForActions && hoveredRowForActions.contains(related)) return;
                                                if (rowActionsHideTimeout) clearTimeout(rowActionsHideTimeout);
                                                rowActionsHideTimeout = setTimeout(function() {
                                                    rowActionsHideTimeout = null;
                                                    hideRowActionsPopover();
                                                }, ROW_ACTIONS_HIDE_MS);
                                            });
                                        }
                                        tableBody.addEventListener('mouseout', function(e) {
                                            var related = e.relatedTarget;
                                            if (isElementUnderRowActionsChrome(related)) return;
                                            if (related && hoveredRowForActions && hoveredRowForActions.contains(related)) return;
                                            if (rowActionsHideTimeout) clearTimeout(rowActionsHideTimeout);
                                            rowActionsHideTimeout = setTimeout(function() {
                                                rowActionsHideTimeout = null;
                                                hideRowActionsPopover();
                                            }, ROW_ACTIONS_HIDE_MS);
                                        });
                                        if (rowActionsPopover) {
                                            rowActionsPopover.addEventListener('mouseover', function() {
                                                if (rowActionsHideTimeout) { clearTimeout(rowActionsHideTimeout); rowActionsHideTimeout = null; }
                                            });
                                            rowActionsPopover.addEventListener('mouseout', function(e) {
                                                var related = e.relatedTarget;
                                                if (related && rowActionsPopover.contains(related)) return;
                                                if (isElementUnderRowActionsChrome(related)) return;
                                                if (related && hoveredRowForActions && hoveredRowForActions.contains(related)) return;
                                                if (rowActionsHideTimeout) clearTimeout(rowActionsHideTimeout);
                                                rowActionsHideTimeout = setTimeout(function() {
                                                    rowActionsHideTimeout = null;
                                                    hideRowActionsPopover();
                                                }, ROW_ACTIONS_HIDE_MS);
                                            });
                                        }
                                    }

                                    // When focusing into any input/select/textarea, clear selection. Hide row-actions when user types (keydown/input) so popup disappears on input
                                    tableBody.addEventListener('focusin', function(e) {
                                        var el = e.target;
                                        if (el && (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') && el.type !== 'hidden') {
                                            var tr = el.closest('tr.data-row');
                                            if (tr && (tr.classList.contains('bg-blue-100') || tr.classList.contains('grand-total-row'))) {
                                                if (el.value === '') {
                                                    el.select();
                                                }
                                            }
                                        }
                                        if (el && el.closest('input, select, textarea')) {
                                            var trF = el.closest('tr.data-row');
                                            var tdF = el.closest('td');
                                            if (trF && tdF && !trF.classList.contains('bg-blue-100') && !trF.classList.contains('grand-total-row')) {
                                                clearSelectionMulti();
                                                setCellSelected(trF, tdF, true);
                                                lastClickedRowMulti = trF;
                                                lastClickedCellMulti = tdF;
                                                setSelectionModeState('manual');
                                                updateFormulaButtonState();
                                                return;
                                            }
                                            clearSelectionMulti();
                                        }
                                    });
                                    tableBody.addEventListener('keydown', function(e) {
                                        var el = e.target;
                                        if (el && (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') && el.type !== 'hidden') {
                                            var tr = el.closest('tr.data-row');
                                            if (tr && (tr.classList.contains('bg-blue-100') || tr.classList.contains('grand-total-row'))) {
                                                if (el.value === '') {
                                                    var isPrintable = !e.ctrlKey && !e.metaKey && !e.altKey && e.key && e.key.length === 1;
                                                    if (isPrintable) {
                                                        el.value = '';
                                                    }
                                                }
                                            }
                                        }
                                        if (el && el.closest('input, select, textarea')) {
                                            hideRowActionsPopover();
                                        }
                                    });
                                    tableBody.addEventListener('input', function(e) {
                                        if (e.target.closest('input, select, textarea')) {
                                            hideRowActionsPopover();
                                        }
                                    });

                                    // When editing blue summary or grand total row inputs, empty values become a dash automatically
                                    tableBody.addEventListener('blur', function(e) {
                                        var el = e.target;
                                        if (!el || (el.tagName !== 'INPUT' && el.tagName !== 'TEXTAREA' && el.tagName !== 'SELECT')) return;
                                        var tr = el.closest('tr.data-row');
                                        if (!tr || (!tr.classList.contains('bg-blue-100') && !tr.classList.contains('grand-total-row'))) return;
                                        var td = el.closest('td');
                                    if (td) {
                                        clearCellFormulaMapping(td);
                                        td.classList.add('manual-override');
                                        td.setAttribute('data-manual-override', '1');
                                    }
                                        if (String(el.value).trim() === '') {
                                            el.value = '';
                                        }
                                    }, true);

                                    // Normalize existing blue rows on initial load
                                    normalizeBlueRowDashes();
                                    // Ensure performance metrics (Variance/Rate/Rating) render with current formatting rules
                                    if (typeof recomputeBlueRowPerformance === 'function' && tableBody) {
                                        var initBlueRows = tableBody.querySelectorAll('tr.data-row.bg-blue-100');
                                        initBlueRows.forEach(function(tr) {
                                            try { recomputeBlueRowPerformance(tr); } catch (e) {}
                                        });
                                    }
                                }

                                // Draggable selection popover (position:fixed - coordinates are viewport-relative)
                                    (function() {
                                        var dragHandle = document.getElementById('selection-popover-drag');
                                        if (!dragHandle || !selectionPopover) return;
                                        var dragStart = { x: 0, y: 0, left: 0, top: 0 };
                                        dragHandle.addEventListener('mousedown', function(e) {
                                            if (e.button !== 0) return;
                                            e.preventDefault();
                                            var rect = selectionPopover.getBoundingClientRect();
                                            dragStart.x = e.clientX;
                                            dragStart.y = e.clientY;
                                            dragStart.left = rect.left;
                                            dragStart.top  = rect.top;
                                            function onMove(ev) {
                                                var dx = ev.clientX - dragStart.x;
                                                var dy = ev.clientY - dragStart.y;
                                                var vw = window.innerWidth  || document.documentElement.clientWidth;
                                                var vh = window.innerHeight || document.documentElement.clientHeight;
                                                var newLeft = Math.max(0, Math.min(vw - selectionPopover.offsetWidth,  dragStart.left + dx));
                                                var newTop  = Math.max(0, Math.min(vh - selectionPopover.offsetHeight, dragStart.top  + dy));
                                                selectionPopover.style.left = newLeft + 'px';
                                                selectionPopover.style.top  = newTop  + 'px';
                                            }
                                            function onUp() {
                                                document.removeEventListener('mousemove', onMove);
                                                document.removeEventListener('mouseup', onUp);
                                            }
                                            document.addEventListener('mousemove', onMove);
                                            document.addEventListener('mouseup', onUp);
                                        });
                                    })();

                                // Clicking anywhere outside the table or popovers clears selection and hides Add row / Separate
                                document.addEventListener('mousedown', function(e) {
                                    if (Date.now() < suppressSelectionClearUntil) return;
                                    // Clicking an entry in a native <select> list often targets <option>, which is not a descendant
                                    // of #selection-popover in the hit-test chain - do not treat as "click outside".
                                    var tn = e.target && e.target.nodeName ? String(e.target.nodeName).toUpperCase() : '';
                                    if (tn === 'OPTION') return;
                                    if (pendingReselectCellsPick && reselectCellsTargetTd) {
                                        var insidePickChrome = (selectionPopover && selectionPopover.contains(e.target))
                                            || (formulaModal && formulaModal.contains(e.target))
                                            || (autocalcModal && autocalcModal.contains(e.target))
                                            || (aggregateChainModal && aggregateChainModal.contains(e.target))
                                            || (campusTargetComparePanel && campusTargetComparePanel.contains(e.target))
                                            || (rowActionsPopover && rowActionsPopover.contains(e.target))
                                            || (rowActionsPopoverBlue && rowActionsPopoverBlue.contains(e.target))
                                            || (rowActionsDotsBtn && rowActionsDotsBtn.contains(e.target));
                                        if (insidePickChrome) return;
                                        var insideTablePick = tableContainerMulti && tableContainerMulti.contains(e.target);
                                        if (!insideTablePick) {
                                            cancelPendingReselectCellsPick();
                                            if (typeof window.showToast === 'function') window.showToast('notice', 'Reselect Cells cancelled.');
                                        } else {
                                            var tdPick = e.target.closest && e.target.closest('td');
                                            var trPick = tdPick && tdPick.closest('tr.data-row');
                                            if (tdPick && trPick && trPick.classList.contains('bg-blue-100')) {
                                                var targetTdSaved = reselectCellsTargetTd;
                                                cancelPendingReselectCellsPick();
                                                e.preventDefault();
                                                e.stopPropagation();
                                                applyBlueReselectCellsFromSourceToTarget(tdPick, targetTdSaved);
                                                return;
                                            }
                                            cancelPendingReselectCellsPick();
                                            if (typeof window.showToast === 'function') window.showToast('notice', 'Reselect Cells cancelled.');
                                            return;
                                        }
                                    }
                                    var isInsideTable = tableContainerMulti && tableContainerMulti.contains(e.target);
                                    var isInsideFormulaPopover = selectionPopover && selectionPopover.contains(e.target);
                                    var isInsideFormulaModal = formulaModal && formulaModal.contains(e.target);
                                    var isInsideRowActions = rowActionsPopover && rowActionsPopover.contains(e.target);
                                    var isInsideRowActionsBlue = rowActionsPopoverBlue && rowActionsPopoverBlue.contains(e.target);
                                    var isInsideRowActionsDots = rowActionsDotsBtn && rowActionsDotsBtn.contains(e.target);
                                    var isInsideCampusCompare = campusTargetComparePanel && campusTargetComparePanel.contains(e.target);
                                    if (!isInsideTable && !isInsideFormulaPopover && !isInsideFormulaModal && !isInsideRowActions && !isInsideRowActionsBlue && !isInsideRowActionsDots && !isInsideCampusCompare) {
                                        clearSelectionMulti();
                                        hideRowActionsPopover();
                                    }
                                });

                                document.addEventListener('keydown', function uapsReselectCellsEsc(e) {
                                    if (!pendingReselectCellsPick) return;
                                    if (e.key !== 'Escape' && e.key !== 'Esc') return;
                                    cancelPendingReselectCellsPick();
                                    if (typeof window.showToast === 'function') window.showToast('notice', 'Reselect Cells cancelled.');
                                    e.preventDefault();
                                    e.stopPropagation();
                                }, true);

                                function showFormulaModal() {
                                    if (!formulaModal) return;
                                    formulaError.classList.add('hidden');
                                    formulaError.textContent = '';
                                    formulaModal.classList.remove('hidden');
                                    updateSelectionLiveHints();
                                    var formulaModalTitle = formulaModal.querySelector('h4');
                                    var formulaModalDesc = document.getElementById('formula-modal-desc');
                                    if (formulaBlueRowOnlyMode) {
                                        if (formulaGrandTotalMode && formulaMultiSourceMode) {
                                            if (formulaModalTitle) formulaModalTitle.textContent = 'Formula (A+B+C...) - Grand Total';
                                            if (formulaModalDesc) formulaModalDesc.textContent = 'Pick multiple source columns (preview from first campus blue row). Result aggregates into the Grand Total cell.';
                                            var selectedGTM = tableBody ? Array.prototype.slice.call(tableBody.querySelectorAll('td.cell-selected')).filter(function(td) {
                                                var tr = td.closest('tr.data-row');
                                                return tr && tr.classList.contains('grand-total-row');
                                            }) : [];
                                            var firstBlueRowM = tableBody ? tableBody.querySelector('tr.data-row.bg-blue-100') : null;
                                            if (selectedGTM.length > 0 && formulaTargetSelect && firstBlueRowM) {
                                                var gtTdM = selectedGTM[0];
                                                var targetColM = getColIndex(gtTdM);
                                                if (targetColM >= 0 && targetColM < fields.length) {
                                                    formulaTargetSelect.value = getFieldKey(fields[targetColM]);
                                                }
                                                formulaTargetSelect.disabled = true;
                                                if (formulaSelectedColumnInfo) {
                                                    var targetLabelM = fields[targetColM] && (fields[targetColM].label || getFieldKey(fields[targetColM])) || 'Target';
                                                    var targetValM = getCellRawValue(gtTdM);
                                                    formulaSelectedColumnInfo.classList.remove('hidden');
                                                    formulaSelectedColumnInfo.textContent = 'Target: Grand Total ' + targetLabelM + ' (value: ' + (String(targetValM || '').trim() || '-') + ')';
                                                }
                                                var targetSelectWrapM = document.getElementById('formula-target-select-wrap');
                                                if (targetSelectWrapM) targetSelectWrapM.classList.add('hidden');
                                                var blueRow = firstBlueRowM;
                                                var grandTotalRowM = gtTdM ? gtTdM.closest('tr.data-row') : null;
                                                var targetCol = targetColM;
                                                function populateSourceWithBlueRowValuesGT(selectEl) {
                                                    if (!selectEl || !grandTotalRowM) return;
                                                    selectEl.innerHTML = '<option value="">- Select column -</option>';
                                                    var tds = window.getRowTdCells(grandTotalRowM);
                                                    var withVals = [], withoutVals = [];
                                                    for (var c = 0; c < fields.length && c < tds.length; c++) {
                                                        if (c === targetCol) continue;
                                                        var f = fields[c];
                                                        var label = (f && (f.label || getFieldKey(f))) || ('Column ' + (c + 1));
                                                        var key = getFieldKey(f);
                                                        var cell = tds[c];
                                                        var val = cell ? getCellRawValue(cell) : '';
                                                        var valStr = String(val || '').trim() || '-';
                                                        var numVal = toNumeric(val);
                                                        var hasNum = !isNaN(numVal) && valStr !== '-' && valStr !== '';
                                                        var item = { key: key, label: label, valStr: valStr, hasNum: hasNum, numVal: numVal };
                                                        if (hasNum) withVals.push(item); else withoutVals.push(item);
                                                    }
                                                    withVals.forEach(function(item) {
                                                        var opt = document.createElement('option');
                                                        opt.value = item.key;
                                                        opt.setAttribute('data-value', String(item.valStr));
                                                        opt.textContent = item.label + ' (' + item.valStr + ')';
                                                        selectEl.appendChild(opt);
                                                    });
                                                    withoutVals.forEach(function(item) {
                                                        var opt = document.createElement('option');
                                                        opt.value = item.key;
                                                        opt.setAttribute('data-value', String(item.valStr));
                                                        opt.textContent = item.label + ' (' + item.valStr + ')';
                                                        selectEl.appendChild(opt);
                                                    });
                                                }
                                                populateSourceWithBlueRowValuesGT(formulaSourceASelect);
                                                populateSourceWithBlueRowValuesGT(formulaSourceBSelect);
                                                var savedMapping = getCellFormulaMapping(gtTdM);
                                                var extraContainer = document.getElementById('formula-sources-extra');
                                                var addSourceBtn = document.getElementById('formula-add-source-btn');
                                                var formulaCustomWrap = document.getElementById('formula-custom-wrap');
                                                var formulaOperationWrap = document.getElementById('formula-operation-wrap');
                                                var formulaCustomExpr = document.getElementById('formula-custom-expr');
                                                if (formulaCustomWrap) formulaCustomWrap.classList.add('hidden');
                                                if (formulaOperationWrap) formulaOperationWrap.classList.remove('hidden');
                                                function addFormulaSourceRowGT(preselectedKey) {
                                                    if (!extraContainer || !blueRow) return;
                                                    var letters = 'CDEFGHIJ';
                                                    var idx = extraContainer.children.length;
                                                    var letter = letters[idx] || String.fromCharCode(67 + idx);
                                                    var wrap = document.createElement('div');
                                                    wrap.className = 'flex items-center gap-2';
                                                    var label = document.createElement('label');
                                                    label.className = 'text-xs font-medium text-gray-600 w-8 shrink-0';
                                                    label.textContent = letter + '.';
                                                    var sel = document.createElement('select');
                                                    sel.className = 'formula-source-select flex-1 border border-gray-300 rounded-lg py-2 px-3 text-sm focus:ring-2 focus:ring-indigo-500 bg-white';
                                                    sel.setAttribute('data-source-letter', letter);
                                                    populateSourceWithBlueRowValuesGT(sel);
                                                    if (preselectedKey) sel.value = preselectedKey;
                                                    var rm = document.createElement('button');
                                                    rm.type = 'button';
                                                    rm.className = 'shrink-0 p-1.5 rounded-md text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors';
                                                    rm.title = 'Remove source';
                                                    rm.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M18 18L6 6"></path></svg>';
                                                    rm.addEventListener('click', function() {
                                                        var removedLetter = sel.getAttribute('data-source-letter');
                                                        wrap.remove();
                                                        var extraOpBtns = document.getElementById('formula-op-btns-extra');
                                                        if (extraOpBtns && removedLetter) {
                                                            var toRemove = extraOpBtns.querySelector('.formula-op-btn-extra[data-char="' + removedLetter + '"]');
                                                            if (toRemove) toRemove.remove();
                                                        }
                                                        updateFormulaPreview();
                                                    });
                                                    wrap.appendChild(label);
                                                    wrap.appendChild(sel);
                                                    wrap.appendChild(rm);
                                                    extraContainer.appendChild(wrap);
                                                    sel.addEventListener('change', updateFormulaPreview);
                                                    if (!preselectedKey && formulaMultiSourceMode) {
                                                        var opPick = formulaOperationSelect ? formulaOperationSelect.value : '';
                                                        var isCustomOpPick = opPick === 'custom' || (typeof opPick === 'string' && opPick.indexOf('custom:') === 0);
                                                        if (isCustomOpPick) {
                                                            var inputEl = document.getElementById('formula-custom-expr');
                                                            if (inputEl) {
                                                                var cur = String(inputEl.value || '').trim();
                                                                var toInsert = cur.length === 0 ? letter : ' + ' + letter;
                                                                var pos = inputEl.selectionStart != null ? inputEl.selectionStart : cur.length;
                                                                var before = cur.substring(0, pos);
                                                                var after = cur.substring(pos);
                                                                inputEl.value = before + toInsert + after;
                                                                inputEl.selectionStart = inputEl.selectionEnd = pos + toInsert.length;
                                                                inputEl.focus();
                                                            }
                                                        }
                                                    }
                                                    var extraBtns = document.getElementById('formula-op-btns-extra');
                                                    if (extraBtns && formulaMultiSourceMode) {
                                                        var existingLetters = [];
                                                        extraBtns.querySelectorAll('.formula-op-btn-extra').forEach(function(b) { existingLetters.push(b.getAttribute('data-char')); });
                                                        if (existingLetters.indexOf(letter) === -1) {
                                                            var btn = document.createElement('button');
                                                            btn.type = 'button';
                                                            btn.className = 'formula-op-btn formula-op-btn-extra px-2.5 py-1.5 text-sm font-mono bg-indigo-50 hover:bg-indigo-100 hover:text-indigo-700 border border-indigo-200 rounded-md transition-colors';
                                                            btn.setAttribute('data-char', letter);
                                                            btn.textContent = letter;
                                                            btn.addEventListener('click', function() {
                                                                var input = document.getElementById('formula-custom-expr');
                                                                if (!input) return;
                                                                var ch = this.getAttribute('data-char') || '';
                                                                var start = input.selectionStart || 0, end = input.selectionEnd || start, text = input.value || '';
                                                                var newText = text.substring(0, start) + ch + text.substring(end);
                                                                input.value = newText;
                                                                input.selectionStart = input.selectionEnd = start + ch.length;
                                                                input.focus();
                                                                if (typeof updateFormulaPreview === 'function') updateFormulaPreview();
                                                            });
                                                            extraBtns.appendChild(btn);
                                                        }
                                                    }
                                                    updateFormulaPreview();
                                                }
                                                if (formulaOperationWrap) formulaOperationWrap.classList.remove('hidden');
                                                if (extraContainer) { extraContainer.classList.remove('hidden'); extraContainer.innerHTML = ''; }
                                                var extraOpBtns = document.getElementById('formula-op-btns-extra');
                                                if (extraOpBtns) extraOpBtns.innerHTML = '';
                                                if (addSourceBtn) addSourceBtn.classList.remove('hidden');
                                                if (formulaOperationSelect) {
                                                    formulaOperationSelect.innerHTML = '';
                                                    var multiOpsGT = [
                                                        { v: 'sum', t: 'Sum (A+B+C...)' },
                                                        { v: 'avg', t: 'Average (A+B+C...)' },
                                                        { v: 'subtract', t: 'A - B (Difference)' },
                                                        { v: 'multiply', t: 'A A B (Product)' },
                                                        { v: 'divide', t: 'A A- B (Quotient)' },
                                                        { v: 'percent_of', t: 'A A- B A 100 (Percent of)' },
                                                        { v: 'sum_over_b_percent', t: '(A + B) A- B A 100' },
                                                        { v: 'diff_over_b_percent', t: '(A - B) A- B A 100' }
                                                    ];
                                                    multiOpsGT.forEach(function(o) {
                                                        var opt = document.createElement('option');
                                                        opt.value = o.v;
                                                        opt.textContent = o.t;
                                                        formulaOperationSelect.appendChild(opt);
                                                    });
                                                    var savedCustom = getSavedCustomFormulas();
                                                    if (savedMapping && savedMapping.custom_expr) {
                                                        var ce = normalizeCustomExpr(savedMapping.custom_expr);
                                                        if (ce) {
                                                            var normed = savedCustom.map(normalizeCustomExpr);
                                                            if (normed.indexOf(ce) === -1) savedCustom.unshift(ce);
                                                        }
                                                    }
                                                    var seenNormGT = {};
                                                    var predefNormGT = ['a + b', 'a - b', 'a \u00D7 b', 'a \u00F7 b', 'a \u00F7 b \u00D7 100', '(a + b) \u00F7 b \u00D7 100', '(a - b) \u00F7 b \u00D7 100'];
                                                    var dedupedGT = [];
                                                    savedCustom.forEach(function(expr) {
                                                        var displayExpr = normalizeCustomExpr(expr) || expr;
                                                        var n = displayExpr.toLowerCase();
                                                        if (seenNormGT[n] || predefNormGT.indexOf(n) !== -1) return;
                                                        seenNormGT[n] = true;
                                                        dedupedGT.push(displayExpr);
                                                        var opt = document.createElement('option');
                                                        opt.value = 'custom:' + displayExpr;
                                                        opt.textContent = displayExpr;
                                                        formulaOperationSelect.appendChild(opt);
                                                    });
                                                    if (dedupedGT.length !== savedCustom.length) {
                                                        try { localStorage.setItem(customFormulasStorageKey, JSON.stringify(dedupedGT)); } catch (e) {}
                                                    }
                                                    var optCustomGT = document.createElement('option');
                                                    optCustomGT.value = 'custom';
                                                    optCustomGT.textContent = 'Custom (enter your own expression)';
                                                    formulaOperationSelect.appendChild(optCustomGT);
                                                }
                                                var savedSources = savedMapping && Array.isArray(savedMapping.source_keys) ? savedMapping.source_keys.slice() : [];
                                                if (savedMapping && savedMapping.sourceA && savedSources.indexOf(savedMapping.sourceA) === -1) savedSources.unshift(savedMapping.sourceA);
                                                if (savedMapping && savedMapping.sourceB && savedSources.indexOf(savedMapping.sourceB) === -1) savedSources.push(savedMapping.sourceB);
                                                for (var siGT = 2; siGT < savedSources.length; siGT++) addFormulaSourceRowGT(savedSources[siGT]);
                                                var hasMultiRestore = savedMapping && (String(savedMapping.ui_calc_type || '').trim() === 'blue-row-formula-multi' || (String(savedMapping.ui_calc_type || '').trim() === 'grand-total' && Array.isArray(savedMapping.source_keys) && savedMapping.source_keys.length > 0));
                                                if (hasMultiRestore) {
                                                    if (savedMapping.sourceA && formulaSourceASelect) formulaSourceASelect.value = savedMapping.sourceA;
                                                    if (savedMapping.sourceB && formulaSourceBSelect) formulaSourceBSelect.value = savedMapping.sourceB;
                                                    var opMultiGT = String(savedMapping.ui_formula_operation || savedMapping.operation || 'sum').trim();
                                                    var customExprValGT = savedMapping.custom_expr ? String(savedMapping.custom_expr).trim() : '';
                                                    if (formulaOperationSelect && opMultiGT) {
                                                        var opToSelectGT = opMultiGT;
                                                        if (opMultiGT === 'custom' && customExprValGT) {
                                                            var normValGT = normalizeCustomExpr(customExprValGT);
                                                            var foundOptGT = Array.prototype.slice.call(formulaOperationSelect.options || []).find(function(o) {
                                                                var v = String(o.value);
                                                                return v.indexOf('custom:') === 0 && normalizeCustomExpr(v.substring(7)) === normValGT;
                                                            });
                                                            opToSelectGT = foundOptGT ? foundOptGT.value : ('custom:' + normValGT);
                                                        }
                                                        var hasOpGT = Array.prototype.slice.call(formulaOperationSelect.options || []).some(function(o) { return String(o.value) === opToSelectGT; });
                                                        if (hasOpGT) formulaOperationSelect.value = opToSelectGT;
                                                        else if (opMultiGT === 'custom' && formulaOperationSelect.querySelector('option[value="custom"]')) formulaOperationSelect.value = 'custom';
                                                    }
                                                    var isCustomOpGT = opMultiGT === 'custom' || String(opMultiGT).indexOf('custom:') === 0;
                                                    if (isCustomOpGT && formulaCustomExpr) {
                                                        var valToShowGT = customExprValGT || (opMultiGT.indexOf('custom:') === 0 ? opMultiGT.substring(7) : '');
                                                        formulaCustomExpr.value = normalizeCustomExpr(valToShowGT) || valToShowGT;
                                                        if (formulaCustomWrap) formulaCustomWrap.classList.remove('hidden');
                                                    } else if (formulaCustomWrap) {
                                                        formulaCustomWrap.classList.add('hidden');
                                                    }
                                                } else if (formulaCustomWrap) {
                                                    formulaCustomWrap.classList.add('hidden');
                                                }
                                                if (addSourceBtn) {
                                                    addSourceBtn.onclick = function() {
                                                        if (extraContainer && extraContainer.children.length < 8) addFormulaSourceRowGT();
                                                    };
                                                }
                                            }
                                            updateFormulaPreview();
                                        } else if (formulaGrandTotalMode) {
                                            if (formulaModalTitle) formulaModalTitle.textContent = 'Formula - Grand Total';
                                            if (formulaModalDesc) formulaModalDesc.textContent = 'Choose operation. Result aggregates from all blue rows across campuses.';
                                            var selectedGT = tableBody ? Array.prototype.slice.call(tableBody.querySelectorAll('td.cell-selected')).filter(function(td) {
                                                var tr = td.closest('tr.data-row');
                                                return tr && tr.classList.contains('grand-total-row');
                                            }) : [];
                                            if (selectedGT.length > 0 && formulaTargetSelect) {
                                                var gtTd = selectedGT[0];
                                                var targetCol = getColIndex(gtTd);
                                                if (targetCol >= 0 && targetCol < fields.length) {
                                                    formulaTargetSelect.value = getFieldKey(fields[targetCol]);
                                                }
                                                formulaTargetSelect.disabled = true;
                                                if (formulaSelectedColumnInfo) {
                                                    var targetLabel = fields[targetCol] && (fields[targetCol].label || getFieldKey(fields[targetCol])) || 'Target';
                                                    formulaSelectedColumnInfo.classList.remove('hidden');
                                                    formulaSelectedColumnInfo.textContent = 'Target: Grand Total ' + targetLabel + ' (aggregates from all blue rows)';
                                                }
                                                var targetSelectWrap = document.getElementById('formula-target-select-wrap');
                                                if (targetSelectWrap) targetSelectWrap.classList.add('hidden');
                                                var grandTotalRow = gtTd ? gtTd.closest('tr.data-row') : null;
                                                if (grandTotalRow) {
                                                    function populateSourceForGrandTotal(selectEl) {
                                                        if (!selectEl) return;
                                                        selectEl.innerHTML = '<option value="">- Select column -</option>';
                                                        var tds = window.getRowTdCells(grandTotalRow);
                                                        for (var c = 0; c < fields.length && c < tds.length; c++) {
                                                            if (c === targetCol) continue;
                                                            var f = fields[c];
                                                            var label = (f && (f.label || getFieldKey(f))) || ('Column ' + (c + 1));
                                                            var cell = tds[c];
                                                            var val = cell ? getCellRawValue(cell) : '';
                                                            var valStr = String(val || '').trim() || '-';
                                                            var opt = document.createElement('option');
                                                            opt.value = getFieldKey(f);
                                                            opt.setAttribute('data-value', String(valStr));
                                                            opt.textContent = label + ' (' + valStr + ')';
                                                            selectEl.appendChild(opt);
                                                        }
                                                    }
                                                    populateSourceForGrandTotal(formulaSourceASelect);
                                                    populateSourceForGrandTotal(formulaSourceBSelect);
                                                }
                                                var extraContainer = document.getElementById('formula-sources-extra');
                                                if (extraContainer) { extraContainer.classList.add('hidden'); extraContainer.innerHTML = ''; }
                                                var addSourceBtn = document.getElementById('formula-add-source-btn');
                                                if (addSourceBtn) addSourceBtn.classList.add('hidden');
                                                var formulaCustomWrap = document.getElementById('formula-custom-wrap');
                                                if (formulaCustomWrap) formulaCustomWrap.classList.add('hidden');
                                                var savedGTMapping = getCellFormulaMapping(gtTd);
                                                if (savedGTMapping && savedGTMapping.sourceA && formulaSourceASelect) formulaSourceASelect.value = savedGTMapping.sourceA;
                                                if (savedGTMapping && savedGTMapping.sourceB && formulaSourceBSelect) formulaSourceBSelect.value = savedGTMapping.sourceB;
                                                if (formulaOperationWrap) formulaOperationWrap.classList.remove('hidden');
                                                if (formulaOperationSelect) {
                                                    formulaOperationSelect.innerHTML = '';
                                                    [{ v: 'sum', t: 'Sum (all blue cells)' }, { v: 'avg', t: 'Average (all blue cells)' }, { v: 'percent_of', t: 'A A- B A 100 (Percent of)' }, { v: 'divide', t: 'A A- B (Quotient)' }, { v: 'sum_over_b_percent', t: '(A + B) A- B A 100' }, { v: 'diff_over_b_percent', t: '(A - B) A- B A 100' }].forEach(function(o) {
                                                        var opt = document.createElement('option');
                                                        opt.value = o.v;
                                                        opt.textContent = o.t;
                                                        formulaOperationSelect.appendChild(opt);
                                                    });
                                                    var op = savedGTMapping ? String(savedGTMapping.ui_formula_operation || 'sum').trim() : 'sum';
                                                    if (formulaOperationSelect.querySelector('option[value="' + op + '"]')) formulaOperationSelect.value = op;
                                                }
                                            }
                                            updateFormulaPreview();
                                        } else {
                                        if (formulaModalTitle) formulaModalTitle.textContent = formulaCustomMode ? 'Formula (Custom) - Blue row only' : (formulaMultiSourceMode ? 'Formula (A+B+C...) - Blue row only' : 'Formula (A & B) - Blue row only');
                                        if (formulaModalDesc) formulaModalDesc.textContent = formulaCustomMode ? 'Enter a custom expression using A and B. Pick Source A and B from this blue row. Result goes in the selected blue cell.' : (formulaMultiSourceMode ? 'Pick source columns from this blue row. Result goes in the selected blue cell.' : 'Pick Source A and B from this blue row. Result goes in the selected blue cell.');
                                        var selectedBlue = tableBody ? Array.prototype.slice.call(tableBody.querySelectorAll('td.cell-selected')).filter(function(td) {
                                            var tr = td.closest('tr.data-row');
                                            return tr && (tr.classList.contains('bg-blue-100') || String(tr.getAttribute('data-manual-total-row') || '') === '1');
                                        }) : [];
                                        if (selectedBlue.length > 0 && formulaTargetSelect) {
                                            var firstBlue = selectedBlue[0];
                                            var blueRow = firstBlue.closest('tr.data-row');
                                            if (window._uapsManualTotalCrossBluesApply && blueRow && String(blueRow.getAttribute('data-manual-total-row') || '') === '1' && formulaModalDesc) {
                                                formulaModalDesc.textContent = 'Manual total: choose the same source columns as on each campus blue row. On Apply, values are read from every campus blue summary row (excluding this row), combined with your operation, and written here.';
                                            }
                                            var targetCol = getColIndex(firstBlue);
                                            if (targetCol >= 0 && targetCol < fields.length) {
                                                formulaTargetSelect.value = getFieldKey(fields[targetCol]);
                                            }
                                            if (formulaTargetSelect) formulaTargetSelect.disabled = true;
                                            if (formulaSelectedColumnInfo) {
                                                var targetLabel = fields[targetCol] && (fields[targetCol].label || getFieldKey(fields[targetCol])) || 'Target';
                                                var targetVal = getCellRawValue(firstBlue);
                                                formulaSelectedColumnInfo.classList.remove('hidden');
                                                formulaSelectedColumnInfo.textContent = 'Target: ' + targetLabel + ' (value: ' + (String(targetVal || '').trim() || '-') + ')';
                                            }
                                            var targetSelectWrap = document.getElementById('formula-target-select-wrap');
                                            if (targetSelectWrap) targetSelectWrap.classList.add('hidden');
                                            var sourceRowForPopulate = blueRow;
                                            if (window._uapsManualTotalCrossBluesApply && blueRow && String(blueRow.getAttribute('data-manual-total-row') || '') === '1' && tableBody) {
                                                var pickBr = tableBody.querySelector('tr.data-row.bg-blue-100:not([data-manual-total-row="1"])');
                                                if (pickBr) sourceRowForPopulate = pickBr;
                                            }
                                            function populateSourceWithBlueRowValues(selectEl) {
                                                if (!selectEl || !sourceRowForPopulate) return;
                                                selectEl.innerHTML = '<option value="">- Select column -</option>';
                                                var tds = window.getRowTdCells(sourceRowForPopulate);
                                                var withVals = [], withoutVals = [];
                                                for (var c = 0; c < fields.length && c < tds.length; c++) {
                                                    if (c === targetCol) continue;
                                                    var f = fields[c];
                                                    var label = (f && (f.label || getFieldKey(f))) || ('Column ' + (c + 1));
                                                    var key = getFieldKey(f);
                                                    var cell = tds[c];
                                                    var val = cell ? getCellRawValue(cell) : '';
                                                    var valStr = String(val || '').trim() || '-';
                                                    var numVal = toNumeric(val);
                                                    var hasNum = !isNaN(numVal) && valStr !== '-' && valStr !== '';
                                                    var item = { key: key, label: label, valStr: valStr, hasNum: hasNum, numVal: numVal };
                                                    if (hasNum) withVals.push(item); else withoutVals.push(item);
                                                }
                                                withVals.forEach(function(item) {
                                                    var opt = document.createElement('option');
                                                    opt.value = item.key;
                                                    opt.setAttribute('data-value', String(item.valStr));
                                                    opt.textContent = item.label + ' (' + item.valStr + ')';
                                                    selectEl.appendChild(opt);
                                                });
                                                withoutVals.forEach(function(item) {
                                                    var opt = document.createElement('option');
                                                    opt.value = item.key;
                                                    opt.setAttribute('data-value', String(item.valStr));
                                                    opt.textContent = item.label + ' (' + item.valStr + ')';
                                                    selectEl.appendChild(opt);
                                                });
                                            }
                                            populateSourceWithBlueRowValues(formulaSourceASelect);
                                            populateSourceWithBlueRowValues(formulaSourceBSelect);
                                            var savedMapping = getCellFormulaMapping(firstBlue);
                                            var extraContainer = document.getElementById('formula-sources-extra');

