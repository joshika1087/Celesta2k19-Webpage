<nav class="navbar navbar-expand-lg navbar-light bg-light">
  <a class="navbar-brand" href="#">Celesta2k19</a>
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>

  <div class="collapse navbar-collapse" id="navbarSupportedContent">
    <ul class="navbar-nav mr-auto">
      <li class="nav-item">
        <a class="nav-link" href="new_register.php">New Registration<span class="sr-only">(current)</span></a>
      </li>
      <li class="nav-item ">
        <a class="nav-link" href="register.php">Register<span class="sr-only">(current)</span></a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="total_register.php">Total Registered</a>
      </li>
      <?php
        if(registrar_logged_in()){
          echo "<li class='nav-item'><a class='nav-link' href='logout.php'>Logout</a></li>";
        }
        else{
          echo "<li class='nav-item'><a class='nav-link' href='login.php'>Login</a></li>";
        }
        ?> 

    </ul>
  </div>
</nav>