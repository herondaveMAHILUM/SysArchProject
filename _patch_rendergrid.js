function renderPcGrid(lab) {
  var grid = document.getElementById('pcGrid');
  grid.innerHTML = '';
  
  // Build a lookup of DB data
  var dbMap = {};
  pcStatusData.forEach(function(pc) { dbMap[pc.pc_number] = pc; });

  // Always render 50 PCs (1-50)
  for (var i = 1; i <= 50; i++) {
    var pc = dbMap[i] || { pc_number: i, is_available: true };
    var seat = document.createElement('div');
    seat.className = 'pc-seat ' + (pc.is_available ? 'available' : 'occupied');
    seat.dataset.pc = i;
    seat.innerHTML = '<div class="pc-seat-icon">' + (pc.is_available ? '🖥️' : '🔴') + '</div>' +
                     '<div class="pc-seat-number">PC ' + i + '</div>' +
                     '<div class="pc-seat-status">' + (pc.is_available ? 'Available' : 'Reserved') + '</div>';
    
    if (pc.is_available) {
      (function(pcNum) {
        seat.addEventListener('click', function() { selectPc(pcNum); });
      })(i);
    }
    
    grid.appendChild(seat);
  }
}