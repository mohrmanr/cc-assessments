# Source from bash only (WSL/Git Bash on Windows; bash on Hetzner):
#   source /mnt/c/Users/Rich/gsa/scripts/gpush.sh
# Git Bash: source /c/Users/Rich/gsa/scripts/gpush.sh
# Hetzner (no source needed): bash /opt/gsa/scripts/gpull.sh gsa --install-bundle-activate
if [[ -z "${BASH_VERSION:-}" ]]; then
  echo "gpush/gpull: source this file from bash, not sh/dash" >&2
  return 1 2>/dev/null || exit 1
fi
#
# Deploy contract (Windows research host -> Hetzner operator host):
#   1. WSL/Git Bash:  gpush gsa -m "..."   (and gpush bt when bt changed)
#   2. Hetzner:       gpull gsa
#                   sudo gpull gsa --install-bundle-activate   # copies activate scripts to /usr/local/lib/gsa-operator-bundle/
#                   sudo systemctl start gsa-operator-bundle-activate.service
#   Never copy scripts by hand from /opt/gsa without pulling first — gpull is the source of truth.
#
# On production (Hetzner): pull-only — source the same file but use gpull only.
# gpush refuses to run on prod hosts unless GPUSH_ALLOW=1.
# WSL dev is detected via WSL_DISTRO_NAME / /proc/version / /mnt/c/... even when
# hostname matches prod (e.g. both named genstatarb).
#
# Env overrides (optional): GSA_REPO, BT_REPO

_gpush_default_repo() {
  local name="$1"
  local here=""
  here="$(git rev-parse --show-toplevel 2>/dev/null || true)"
  if [[ -n "$here" && -d "$here/.git" ]]; then
    local base
    base="$(basename "$here")"
    if [[ "$name" == "gsa" && "$base" == "gsa" ]] || [[ "$name" == "bt" && "$base" == "bt" ]]; then
      printf '%s\n' "$here"
      return 0
    fi
  fi
  local -a candidates=()
  case "$name" in
    gsa)
      if _gpush_is_wsl_dev; then
        candidates=("${GSA_REPO:-}" "/mnt/c/Users/Rich/gsa" "/c/Users/Rich/gsa" "$HOME/gsa" "/opt/gsa")
      else
        candidates=("${GSA_REPO:-}" "$HOME/gsa" "/mnt/c/Users/Rich/gsa" "/c/Users/Rich/gsa" "/opt/gsa")
      fi
      ;;
    bt)
      if _gpush_is_wsl_dev; then
        candidates=("${BT_REPO:-}" "/mnt/c/Users/Rich/bt" "/c/Users/Rich/bt" "$HOME/bt" "/opt/bt")
      else
        candidates=("${BT_REPO:-}" "$HOME/bt" "/mnt/c/Users/Rich/bt" "/c/Users/Rich/bt" "/opt/bt")
      fi
      ;;
    *)
      return 1
      ;;
  esac
  local c
  for c in "${candidates[@]}"; do
    [[ -n "$c" && -d "$c/.git" ]] && { printf '%s\n' "$c"; return 0; }
  done
  return 1
}

_gpush_is_wsl_dev() {
  # WSL on Windows research host — allow push even if hostname matches prod (e.g. genstatarb).
  if [[ -n "${WSL_DISTRO_NAME:-}" ]]; then
    return 0
  fi
  if grep -qi microsoft /proc/version 2>/dev/null; then
    return 0
  fi
  if [[ -d /mnt/c/Users/Rich/gsa/.git ]] || [[ -d /mnt/c/Users/Rich/bt/.git ]]; then
    return 0
  fi
  return 1
}

_gpush_blocked() {
  if [[ "${GPUSH_ALLOW:-}" == "1" ]]; then
    return 1
  fi
  if _gpush_is_wsl_dev; then
    return 1
  fi
  local hn
  hn="$(hostname 2>/dev/null || true)"
  case "$hn" in
    genstatarb*)
      echo "gpush: blocked on production host '$hn' (pull-only). Push from WSL instead." >&2
      echo "gpush: use gpull here. Override: GPUSH_ALLOW=1 gpush ..." >&2
      return 0
      ;;
  esac
  # Linux deploy tree without WSL — typical Hetzner layout
  if [[ -d /opt/gsa/.git ]] && [[ ! -d /mnt/c/Users/Rich/gsa/.git ]]; then
    echo "gpush: blocked — production layout detected (/opt/gsa). Push from WSL." >&2
    echo "gpush: use gpull here. Override: GPUSH_ALLOW=1 gpush ..." >&2
    return 0
  fi
  return 1
}

_gpush_one() {
  local repo="$1"
  local msg="$2"
  if [[ ! -d "$repo/.git" ]]; then
    echo "gpush: skip - not a git repo: $repo" >&2
    return 0
  fi
  (
    cd "$repo" || exit 1
    local branch
    branch="$(git branch --show-current 2>/dev/null || true)"
    local label="$repo${branch:+ ($branch)}"
    if [[ -z "$(git status --porcelain)" ]]; then
      if git rev-parse --abbrev-ref '@{u}' >/dev/null 2>&1; then
        local ahead
        ahead="$(git rev-list --count '@{u}..HEAD' 2>/dev/null || echo 0)"
        if [[ "${ahead:-0}" -gt 0 ]]; then
          echo "=== $label — clean, pushing ${ahead} unpushed commit(s) ==="
          git push
          exit $?
        fi
      fi
      echo "[$label] clean — nothing to commit or push"
      exit 0
    fi
    echo "=== $label ==="
    git add -A
    git commit -m "$msg" && git push
  )
}

gpush() {
  if _gpush_blocked; then
    return 1
  fi
  local msg=""
  local mode="both"
  while [[ $# -gt 0 ]]; do
    case "$1" in
      gsa|bt) mode="$1"; shift ;;
      --here|-h) mode="here"; shift ;;
      -m) shift; msg="${1:-}"; shift || true ;;
      --) shift; msg="$*"; break ;;
      -*) echo "gpush: unknown option: $1" >&2; return 1 ;;
      *) msg="$*"; break ;;
    esac
  done
  if [[ -z "$msg" ]]; then read -rp "Commit message: " msg; fi
  if [[ -z "$msg" ]]; then echo "Aborted: empty commit message" >&2; return 1; fi
  local -a repos=()
  case "$mode" in
    gsa) repos=("$(_gpush_default_repo gsa)") ;;
    bt) repos=("$(_gpush_default_repo bt)") ;;
    here) repos=("$(git rev-parse --show-toplevel 2>/dev/null)") ;;
    both) repos=("$(_gpush_default_repo bt)" "$(_gpush_default_repo gsa)") ;;
  esac
  local repo
  local status=0
  for repo in "${repos[@]}"; do
    [[ -n "$repo" ]] || continue
    _gpush_one "$repo" "$msg" || status=1
  done
  return "$status"
}

gpull() {
  local mode="both"
  local install_bundle=0
  while [[ $# -gt 0 ]]; do
    case "$1" in
      gsa|bt) mode="$1"; shift ;;
      --here|-h) mode="here"; shift ;;
      --install-bundle-activate)
        install_bundle=1
        shift
        ;;
      *)
        echo "gpull: unknown arg: $1" >&2
        return 1
        ;;
    esac
  done
  local -a repos=()
  case "$mode" in
    gsa) repos=("$(_gpush_default_repo gsa)") ;;
    bt) repos=("$(_gpush_default_repo bt)") ;;
    here) repos=("$(git rev-parse --show-toplevel 2>/dev/null)") ;;
    both) repos=("$(_gpush_default_repo bt)" "$(_gpush_default_repo gsa)") ;;
  esac
  local repo
  local status=0
  local pulled_gsa=0
  for repo in "${repos[@]}"; do
    [[ -n "$repo" && -d "$repo/.git" ]] || continue
    echo "=== pull $repo ==="
    (cd "$repo" && git pull) || status=1
    if [[ "$repo" == "$(_gpush_default_repo gsa)" ]]; then
      pulled_gsa=1
    fi
  done
  if [[ "$install_bundle" -eq 1 ]]; then
    if [[ "$mode" != "gsa" && "$mode" != "both" ]]; then
      echo "gpull: --install-bundle-activate requires gpull gsa or gpull both" >&2
      return 1
    fi
    if [[ "$pulled_gsa" -eq 0 ]]; then
      echo "gpull: gsa repo not found — skip bundle-activate install" >&2
      return 1
    fi
    local gsa_repo
    gsa_repo="$(_gpush_default_repo gsa)"
    if [[ ! -f "$gsa_repo/scripts/install_operator_bundle_activate.sh" ]]; then
      echo "gpull: missing $gsa_repo/scripts/install_operator_bundle_activate.sh" >&2
      return 1
    fi
    echo "=== install operator bundle activate scripts ==="
    bash "$gsa_repo/scripts/install_operator_bundle_activate.sh" || status=1
  fi
  return "$status"
}