# ALREADY APPLIED (2026-10-06) - kept as a record only. DO NOT RE-RUN.
# Converts the customer "My bookings" sidebar link into an accordion with
# "Venue Booking Request" and "My Booking History" sub-links.
# It replaces fixed line indices, so re-running it will corrupt sidebar.php.
# Original path at run time: www/ (run from the www folder).
import sys
with open("coh-pms/views/layouts/sidebar.php", "r", encoding="utf-8") as f:
    lines = f.readlines()

new_lines = []
for i, line in enumerate(lines):
    if i == 110: # This is the start of the <li> for My bookings (line index 110 -> line 111)
        pass # We will handle replacement in a block
    new_lines.append(line)

replacement = """                <?php
                $myBookingsExpanded = str_contains(current_page_attr('booking/customerList'), 'page');
                ?>
                <li class="accordion-item <?php echo $myBookingsExpanded ? 'expanded' : ''; ?>">
                    <button class="accordion-header" type="button" aria-expanded="<?php echo $myBookingsExpanded ? 'true' : 'false'; ?>">
                        <span><span class="nav-icon" aria-hidden="true">&#x1F4C5;</span>My bookings</span>
                        <span class="accordion-chevron" aria-hidden="true"></span>
                    </button>
                    <div class="accordion-content">
                        <a href="<?php echo e(BASE_URL . '/?r=booking/customerList#request'); ?>" class="tab-link" data-target="request">Venue Booking Request</a>
                        <a href="<?php echo e(BASE_URL . '/?r=booking/customerList#history'); ?>" class="tab-link" data-target="history">My Booking History</a>
                    </div>
                </li>
"""
lines[110:115] = [replacement]
with open("coh-pms/views/layouts/sidebar.php", "w", encoding="utf-8") as f:
    f.writelines(lines)
print("Done")

