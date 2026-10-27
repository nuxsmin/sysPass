#!/usr/bin/env bash

set -euo pipefail

cd "$(dirname "$0")/.."

count_files() {
    grep -rlE --include='*.php' "$1" "$2" 2>/dev/null | wc -l | tr -d ' '
}

count_lines() {
    grep -rhE --include='*.php' "$1" "$2" 2>/dev/null | wc -l | tr -d ' '
}

count_without_group() {
    find tests/SP -name '*Test.php' -print0 | xargs -0 -r grep -L '#\[Group' 2>/dev/null | wc -l | tr -d ' '
}

core_to_infrastructure_outside_composition() {
    grep -rlE --include='*.php' '^use SP\\Infrastructure\\' lib/SP/Core 2>/dev/null \
        | grep -v '/Definitions/' \
        | wc -l | tr -d ' '
}

core_to_infrastructure_composition() {
    grep -rlE --include='*.php' '^use SP\\Infrastructure\\' lib/SP/Core/Definitions 2>/dev/null \
        | wc -l | tr -d ' '
}

controller_areas() {
    local tested=0
    local untested=0
    local area

    for directory in app/modules/web/Controllers/*/; do
        area="$(basename "$directory")"

        if [ -d "tests/SP/Modules/Web/Controllers/${area}" ]; then
            tested=$((tested + 1))
        else
            untested=$((untested + 1))
        fi
    done

    printf '%d/%d' "$untested" "$((tested + untested))"
}

web_controller_total() {
    find app/modules/web/Controllers -name '*.php' | wc -l | tr -d ' '
}

api_controller_total() {
    find app/modules/api/Controllers -name '*.php' | wc -l | tr -d ' '
}


cross_context_imports() {
    local total=0
    local output=""
    local context
    local count

    for directory in lib/SP/Domain/*/; do
        context="$(basename "$directory")"

        case "$context" in
            Common|Core) continue ;;
        esac

        count=$(grep -rhE --include='*.php' '^use SP\\Domain\\[A-Za-z]+\\' "$directory" 2>/dev/null \
            | grep -vE '\\(Ports|Dtos|Models|Enums|Exceptions)\\' \
            | sed -E 's/^use SP\\Domain\\([A-Za-z]+)\\.*/\1/' \
            | grep -vE "^(${context}|Common|Core)$" \
            | wc -l | tr -d ' ')

        if [ "$count" -gt 0 ]; then
            output="${output}${context}=${count} "
            total=$((total + count))
        fi
    done

    printf '%d (%s)' "$total" "${output% }"
}

print_metric() {
    printf '%-4s %-62s %s\n' "$1" "$2" "$3"
}

echo "sysPass architecture metrics"
echo

print_metric "M1" "Domain files importing any outer layer" "$(count_files '^use SP\\(Infrastructure|Core|Modules|Util)\\' lib/SP/Domain)"
print_metric "M2" "Domain files importing Infrastructure" "$(count_files '^use SP\\Infrastructure\\' lib/SP/Domain)"
print_metric "M3" "Domain files importing SP\\Core" "$(count_files '^use SP\\Core\\' lib/SP/Domain)"
print_metric "M4" "Domain files importing SP\\Core\\Application" "$(count_files '^use SP\\Core\\Application;' lib/SP/Domain)"
print_metric "M5" "Domain files calling __u()" "$(count_files '\b__u\(' lib/SP/Domain)"
print_metric "M5s" "Domain __u() call sites" "$(grep -rhoE '\b__u\(' --include='*.php' lib/SP/Domain 2>/dev/null | wc -l | tr -d ' ')"

ports_importing_infrastructure=$(
    find lib/SP/Domain -path '*Ports*' -name '*.php' -print0 \
        | xargs -0 -r grep -lE '^use SP\\Infrastructure\\' 2>/dev/null \
        | wc -l | tr -d ' ' || true
)
ports_total=$(find lib/SP/Domain -path '*Ports*' -name '*.php' | wc -l | tr -d ' ')
print_metric "M6" "Port files importing Infrastructure" "${ports_importing_infrastructure}/${ports_total}"

print_metric "M7" "Module files importing Infrastructure" "$(count_files '^use SP\\Infrastructure\\' app/modules)"
print_metric "M8" "Module use lines importing Domain Services" "$(count_lines '^use SP\\Domain\\[A-Za-z]+\\Services\\' app/modules)"
print_metric "M9" "Web controllers using #[Action]" "$(count_files '#\[Action' app/modules/web/Controllers)/$(web_controller_total)"
print_metric "M10" "Web controllers using legacy JSON responses" "$(count_files 'returnJsonResponse|JsonMessage' app/modules/web/Controllers)"
print_metric "M11" "API controllers using #[Action]" "$(count_files '#\[Action' app/modules/api)/$(find app/modules/api -name '*.php' | wc -l | tr -d ' ')"
print_metric "M12" "lib/ files referencing SP\\Modules" "$(count_files 'SP\\Modules' lib)"
print_metric "M13" "Core -> Infrastructure outside composition" "$(core_to_infrastructure_outside_composition) (+$(core_to_infrastructure_composition) composition)"
print_metric "M14" "Domain test files importing Infrastructure" "$(count_files '^use SP\\Infrastructure\\' tests/SP/Domain)"
print_metric "M15" "Web controller areas without integration tests" "$(controller_areas)"
print_metric "M16" "Test files without #[Group]" "$(count_without_group)"
print_metric "M17" "Concrete cross-context Domain imports" "$(cross_context_imports)"
