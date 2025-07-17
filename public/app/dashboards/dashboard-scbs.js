$(function () {
  'use strict'

  // Sales and Quotes Chart
  var salesChartCanvas = document.getElementById('sales-chart').getContext('2d')
  
  var salesChartData = {
    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
    datasets: [
      {
        label: 'Buses Sold',
        backgroundColor: 'rgba(54, 162, 235, 0.2)',
        borderColor: 'rgba(54, 162, 235, 1)',
        borderWidth: 2,
        pointRadius: 4,
        pointBackgroundColor: 'rgba(54, 162, 235, 1)',
        data: [2, 1, 3, 2, 4, 1, 2]
      },
      {
        label: 'Quotes Sent',
        backgroundColor: 'rgba(75, 192, 192, 0.2)',
        borderColor: 'rgba(75, 192, 192, 1)',
        borderWidth: 2,
        pointRadius: 4,
        pointBackgroundColor: 'rgba(75, 192, 192, 1)',
        data: [5, 3, 7, 4, 6, 3, 5]
      },
      {
        label: 'Inquiries Received',
        backgroundColor: 'rgba(255, 206, 86, 0.2)',
        borderColor: 'rgba(255, 206, 86, 1)',
        borderWidth: 2,
        pointRadius: 4,
        pointBackgroundColor: 'rgba(255, 206, 86, 1)',
        data: [8, 6, 9, 7, 10, 5, 7]
      }
    ]
  }

  var salesChartOptions = {
    maintainAspectRatio: false,
    responsive: true,
    plugins: {
      legend: {
        display: true,
        position: 'top'
      }
    },
    scales: {
      x: {
        grid: {
          display: false
        }
      },
      y: {
        grid: {
          display: true,
          color: 'rgba(0,0,0,0.1)'
        },
        beginAtZero: true,
        max: 12
      }
    },
    interaction: {
      intersect: false,
      mode: 'index'
    }
  }

  // Create the sales chart
  var salesChart = new Chart(salesChartCanvas, {
    type: 'line',
    data: salesChartData,
    options: salesChartOptions
  })

  // Update data every 30 seconds (simulation)
  setInterval(function() {
    // Here you can add AJAX calls to update data in real time
    console.log('Updating bus dashboard data...');
  }, 30000);

  // Configure tooltips for better experience
  $('[data-bs-toggle="tooltip"]').tooltip();

  // Hover effects for stats cards
  $('.card.text-white').hover(
    function() {
      $(this).addClass('shadow-lg').css('transform', 'translateY(-2px)');
    },
    function() {
      $(this).removeClass('shadow-lg').css('transform', 'translateY(0)');
    }
  );

  // Animation for brand progress bars
  $('.progress-bar').each(function() {
    var progressBar = $(this);
    var width = progressBar.css('width');
    progressBar.css('width', '0%');
    
    setTimeout(function() {
      progressBar.animate({
        width: width
      }, 1000);
    }, 500);
  });

  // Hover effect for main button
  $('.btn-lg').hover(
    function() {
      $(this).css('transform', 'scale(1.05)');
    },
    function() {
      $(this).css('transform', 'scale(1)');
    }
  );

});