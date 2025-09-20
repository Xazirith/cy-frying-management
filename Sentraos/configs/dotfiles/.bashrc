#
# ~/.bashrc
#

# If not running interactively, don't do anything
[[ $- != *i* ]] && return

alias ls='ls --color=auto'
alias grep='grep --color=auto'
PS1="\[\e[38;2;165;108;255m\][EclipzaOS]\[\e[0m\] \u@\h:\w ❯ "
export PATH="$HOME/.local/bin:$PATH"
