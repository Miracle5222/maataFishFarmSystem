<?php
// Centralized navigation with dropdowns and sub-links for each main section.
?>
<!-- Links (centralized navigation) -->
<ul class="sidenav-inner py-1">

    <!-- Dashboards -->
    <li class="sidenav-item active">
        <a href="index.php" class="sidenav-link">
            <i class="sidenav-icon feather icon-home"></i>
            <div>Dashboards</div>
            <div class="pl-1 ml-auto">
                <div class="badge badge-danger">Hot</div>
            </div>
        </a>
    </li>

    <li class="sidenav-divider mb-1"></li>

    <li class="sidenav-header small font-weight-semibold">Main</li>

    <!-- Orders -->
    <li class="sidenav-item">
        <a href="javascript:" class="sidenav-link sidenav-toggle">
            <i class="sidenav-icon feather icon-shopping-cart"></i>
            <div>Orders</div>
        </a>
        <ul class="sidenav-menu">
            <li class="sidenav-item"><a href="orders_view.php" class="sidenav-link">
                    <div>View Orders</div>
                </a></li>
         
         
            <li class="sidenav-item"><a href="admin_menu_order.php" class="sidenav-link">
                    <div>Menu Order</div>
                </a></li>
            <li class="sidenav-item"><a href="menu_orders_view.php" class="sidenav-link">
                    <div>View Menu Orders</div>
                </a></li>
        </ul>
    </li>

    <!-- Reservation -->
    <li class="sidenav-item">
        <a href="javascript:" class="sidenav-link sidenav-toggle">
            <i class="sidenav-icon feather icon-calendar"></i>
            <div>Reservation</div>
        </a>
        <ul class="sidenav-menu">
            <li class="sidenav-item"><a href="reservations_list.php" class="sidenav-link">
                    <div>View Reservations</div>
                </a></li>
            <li class="sidenav-item"><a href="cancelled_reservations.php" class="sidenav-link">
                    <div>Cancelled Reservation</div>
                </a></li>

        </ul>
    </li>

    <!-- Reports -->
    <li class="sidenav-item">
        <a href="javascript:" class="sidenav-link sidenav-toggle">
            <i class="sidenav-icon feather icon-bar-chart-2"></i>
            <div>Reports</div>
        </a>
        <ul class="sidenav-menu">
            <li class="sidenav-item"><a href="reports_sales.php" class="sidenav-link">
                    <div>Sales Report</div>
                </a></li>
            <li class="sidenav-item"><a href="reports_revenue.php" class="sidenav-link">
                    <div>Revenue Report</div>
                </a></li>
        </ul>
    </li>

    <!-- Staff -->
    <li class="sidenav-item">
        <a href="javascript:" class="sidenav-link sidenav-toggle">
            <i class="sidenav-icon feather icon-users"></i>
            <div>Manage Employee</div>
        </a>
        <ul class="sidenav-menu">
            <li class="sidenav-item"><a href="staff_list.php" class="sidenav-link">
                    <div>Employee List</div>
                </a></li>
            <li class="sidenav-item"><a href="staff_add.php" class="sidenav-link">
                    <div>Add Employee</div>
                </a></li>
        </ul>
    </li>

    <!-- Calendar -->
    <li class="sidenav-item">
        <a href="javascript:" class="sidenav-link sidenav-toggle">
            <i class="sidenav-icon feather icon-calendar"></i>
            <div>Calendar</div>
        </a>
        <ul class="sidenav-menu">
            <li class="sidenav-item"><a href="calendar_month.php" class="sidenav-link">
                    <div>Monthly View</div>
                </a></li>
           
        </ul>
    </li>

    <!-- Availability -->
    <li class="sidenav-item">
        <a href="javascript:" class="sidenav-link sidenav-toggle">
            <i class="sidenav-icon feather icon-check-square"></i>
            <div>Availability</div>
        </a>
        <ul class="sidenav-menu">
            <li class="sidenav-item"><a href="availability_check.php" class="sidenav-link">
                    <div>Check Availability</div>
                </a></li>
            <li class="sidenav-item"><a href="availability_set.php" class="sidenav-link">
                    <div>Set Availability</div>
                </a></li>
        </ul>
    </li>

    <!-- Customers -->
    <li class="sidenav-item">
        <a href="javascript:" class="sidenav-link sidenav-toggle">
            <i class="sidenav-icon feather icon-user"></i>
            <div>Customers</div>
        </a>
        <ul class="sidenav-menu">
            <li class="sidenav-item"><a href="customers_list.php" class="sidenav-link">
                    <div>Customer List</div>
                </a></li>
          
      
        </ul>
    </li>

    <!-- Products -->
    <li class="sidenav-item">
        <a href="javascript:" class="sidenav-link sidenav-toggle">
            <i class="sidenav-icon feather icon-package"></i>
            <div>Products</div>
        </a>
        <ul class="sidenav-menu">
            <!-- Fish group -->
            <li class="sidenav-item"><a href="products_fish.php" class="sidenav-link">
                    <div>View Fish Species</div>
                </a></li>
         

            <!-- Menu / Food group -->
            <li class="sidenav-item"><a href="products_menu.php" class="sidenav-link">
                    <div>View Menu</div>
                </a></li>
            <li class="sidenav-item"><a href="products_add.php?category=food" class="sidenav-link">
                    <div>Add Menu Item</div>
                </a></li>
   <li class="sidenav-item"><a href="products_add.php?category=fish" class="sidenav-link">
                    <div>Add Fish</div>
                </a></li>
            <!-- Inventory -->
            <!-- <li class="sidenav-item"><a href="inventory_manage.php" class="sidenav-link">
                    <div>Manage Inventory</div>
                </a></li> -->
        </ul>
    </li>

    <li class="sidenav-divider mb-1"></li>

    <!-- <li class="sidenav-header small font-weight-semibold">Tools</li>
    <li class="sidenav-item">
        <a href="admin_profile.php" class="sidenav-link">
            <i class="sidenav-icon feather icon-user"></i>
            <div>My Profile</div>
        </a>
    </li>
    <li class="sidenav-item">
        <a href="user_profile.php" class="sidenav-link">
            <i class="sidenav-icon feather icon-settings"></i>
            <div>Settings</div>
        </a>
    </li> -->

</ul>