.PHONY : all
all :

.PHONY : watch
watch :
	watchexec -- find www/var/cache -type f -delete
