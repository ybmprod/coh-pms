# ALREADY APPLIED (2026-10-07) - kept as a record only. DO NOT RE-RUN.
# Splits the User Management list in views/staff/users.php into two tabs:
# #staff (council staff) and #customers (public customers).
# Re-running it would fail or produce broken markup.
# Original path at run time: www/ (run from the www folder).
import sys

with open("coh-pms/views/staff/users.php", "r", encoding="utf-8") as f:
    content = f.read()

# Split the PHP array at the top
php_replace = """$users = $users ?? [];
$allowedRoles = [ROLE_BOOKING_OFFICER, ROLE_REVENUE_OFFICER, ROLE_ADMINISTRATOR, ROLE_COUNCIL_MANAGEMENT];
$staffUsers = array_filter($users, fn($u) => $u['role'] !== ROLE_CUSTOMER);
$customerUsers = array_filter($users, fn($u) => $u['role'] === ROLE_CUSTOMER);"""

content = content.replace(
    "$users = $users ?? [];\n$allowedRoles = [ROLE_BOOKING_OFFICER, ROLE_REVENUE_OFFICER, ROLE_ADMINISTRATOR, ROLE_COUNCIL_MANAGEMENT];",
    php_replace
)

# Extract the table structure
table_start = content.find('<table class="data-table">')
table_end = content.find('</table>', table_start) + len('</table>')
table_html = content[table_start:table_end]

staff_table = table_html.replace('foreach ($users as $existingUser):', 'foreach ($staffUsers as $existingUser):')
customer_table = table_html.replace('foreach ($users as $existingUser):', 'foreach ($customerUsers as $existingUser):')

# Replace the single `#list` div with `#staff` and `#customers` divs
old_list_div_start = content.find('<div id="list" class="card tab-section active" data-tab="list">')
old_list_div_end = content.find('</div>\n        </section>', old_list_div_start) + len('</div>')

new_list_divs = f"""<div id="staff" class="card tab-section active" data-tab="staff">
                <h2>Staff List</h2>
                {staff_table}
            </div>

            <div id="customers" class="card tab-section" data-tab="customers">
                <h2>Customers</h2>
                {customer_table}
            </div>"""

content = content[:old_list_div_start] + new_list_divs + content[old_list_div_end:]

with open("coh-pms/views/staff/users.php", "w", encoding="utf-8") as f:
    f.write(content)

print("Done")

