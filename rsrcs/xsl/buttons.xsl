<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/1999/xhtml">

  <xsl:template match="button[not(@href) and not(@ext)]" name="button">
    <xsl:param name="value" select="."/>

    <input alt="{$value}" class="button {@class}" type="image" src="?/Yks/Scripts/Imgs/titles//{@theme}|{$value}">
        <xsl:if test="@confirm">
            <xsl:attribute name="onclick">return window.confirm(<xsl:value-of select="@confirm"/>+' ?')</xsl:attribute>
        </xsl:if>
        <xsl:if test="name()='button'">
          <xsl:if test="@ks_action">
            <xsl:attribute name="name">ks_action[<xsl:value-of select="@ks_action"/>]</xsl:attribute>
          </xsl:if>
          <xsl:copy-of select="@name|@src|@onclick|@id|@style|@alt|@title"/>
        </xsl:if>
    </input>

  </xsl:template>

  <xsl:template match="button[@ext|@href]" name="button_href">
    <xsl:param name="href" select="@href|@ext"/>
    <xsl:param name="target" select="@target"/>
    <xsl:param name="value" select="."/>
    <xsl:variable name="ext"><xsl:if test="@ext">ext</xsl:if></xsl:variable>
    <a href="{$href}" target="{$target}" class="button {@class} {$ext}">
        <xsl:copy-of select="@onclick|@style"/>
        <img src="?/Yks/Scripts/Imgs/titles//{@theme}|{$value}" alt="{$value}">
            <xsl:copy-of select="@src"/>
        </img>
    </a>
  </xsl:template>

</xsl:stylesheet>
